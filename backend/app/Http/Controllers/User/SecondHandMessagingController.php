<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SecondHandConversation;
use App\Models\SecondHandListing;
use App\Models\SecondHandMessage;
use App\Models\SecondHandMessageAttachment;
use App\Models\SecondHandMessageModerationLog;
use App\Models\SecondHandUserBlock;
use App\Models\SecondHandVerification;
use App\Mail\SecondHandFirstMessageMail;
use App\Events\SecondHandMessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class SecondHandMessagingController extends Controller
{
    private function spamKey(string $suffix, int $userId, ?int $scopeId = null): string
    {
        return 'c2c:msg:' . $suffix . ':u:' . $userId . ($scopeId ? (':s:' . $scopeId) : '');
    }

    private function enforceRateLimitOrAbort(array $ctx): void
    {
        $userId = (int) ($ctx['sender_id'] ?? 0);
        if (!$userId) return;

        $globalMax = (int) env('SECOND_HAND_MSG_GLOBAL_PER_MIN', 20);
        $globalKey = $this->spamKey('global', $userId);
        if (RateLimiter::tooManyAttempts($globalKey, $globalMax)) {
            $this->logModeration($ctx, 'rate_limit', 'global_per_min');
            abort(429, 'Çok hızlı mesaj gönderiyorsunuz. Lütfen biraz bekleyin.');
        }
        RateLimiter::hit($globalKey, 60);

        // Konuşma bazlı (aynı kişiye spam)
        if (!empty($ctx['conversation_id'])) {
            $convMax = (int) env('SECOND_HAND_MSG_CONV_PER_30S', 8);
            $convKey = $this->spamKey('conv', $userId, (int) $ctx['conversation_id']);
            if (RateLimiter::tooManyAttempts($convKey, $convMax)) {
                $this->logModeration($ctx, 'rate_limit', 'conv_per_30s');
                abort(429, 'Bu konuşmada çok hızlı mesaj gönderiyorsunuz. Lütfen biraz bekleyin.');
            }
            RateLimiter::hit($convKey, 30);
        }
    }

    private function enforceDuplicateOrAbort(?string $body, array $ctx): void
    {
        $userId = (int) ($ctx['sender_id'] ?? 0);
        $convId = (int) ($ctx['conversation_id'] ?? 0);
        $listingId = (int) ($ctx['listing_id'] ?? 0);
        $text = trim(mb_strtolower((string) ($body ?? '')));
        if ($text === '' || !$userId) return;

        $scope = $convId ?: $listingId;
        if (!$scope) return;

        $hash = sha1($text);
        $key = 'c2c:dup:u:' . $userId . ':s:' . $scope . ':h:' . $hash;
        $ttl = (int) env('SECOND_HAND_MSG_DUP_TTL', 30);
        if (Cache::has($key)) {
            $this->logModeration($ctx, 'duplicate', $hash);
            abort(422, 'Aynı mesajı tekrar gönderemezsiniz. Lütfen farklı bir mesaj yazın.');
        }
        Cache::put($key, 1, $ttl);
    }

    private function logModeration(array $ctx, string $reason, ?string $matched = null): void
    {
        try {
            SecondHandMessageModerationLog::query()->create([
                'conversation_id' => $ctx['conversation_id'] ?? null,
                'listing_id' => $ctx['listing_id'] ?? null,
                'sender_id' => (int) ($ctx['sender_id'] ?? 0),
                'receiver_id' => $ctx['receiver_id'] ?? null,
                'body' => (string) ($ctx['body'] ?? ''),
                'reason' => $reason,
                'matched' => $matched,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function ensureNotBlocked(int $a, int $b): void
    {
        $blocked = SecondHandUserBlock::query()
            ->where(function ($q) use ($a, $b) {
                $q->where('blocker_id', $a)->where('blocked_id', $b);
            })
            ->orWhere(function ($q) use ($a, $b) {
                $q->where('blocker_id', $b)->where('blocked_id', $a);
            })
            ->exists();

        abort_if($blocked, 403, 'Bu kullanıcıyla mesajlaşamazsınız.');
    }

    public function blockUser(Request $request)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $request->validate([
            'blocked_id' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:100'],
        ]);

        $blockedId = (int) $request->input('blocked_id');
        abort_if($blockedId === (int) $user->id, 422, 'Kendinizi engelleyemezsiniz.');

        SecondHandUserBlock::query()->updateOrCreate(
            ['blocker_id' => (int) $user->id, 'blocked_id' => $blockedId],
            ['reason' => $request->input('reason')]
        );

        return response()->json(['message' => 'Kullanıcı engellendi.'], 201);
    }

    public function unblockUser(Request $request, $blockedId)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        SecondHandUserBlock::query()
            ->where('blocker_id', (int) $user->id)
            ->where('blocked_id', (int) $blockedId)
            ->delete();

        return response()->json(['message' => 'Engel kaldırıldı.']);
    }

    private function profanityList(): array
    {
        $raw = (string) env('SECOND_HAND_BLOCKED_WORDS', '');
        if (trim($raw) !== '') {
            return array_values(array_filter(array_map('trim', explode(',', mb_strtolower($raw)))));
        }
        // minimum liste (gerekirse .env ile genişletilir)
        return ['amk', 'aq', 'orospu', 'piç', 'siktir', 'yarrak', 'ananı', 'göt', 'salak'];
    }

    private function checkProfanityOrAbort(?string $body, array $ctx = []): void
    {
        $text = trim(mb_strtolower((string) ($body ?? '')));
        if ($text === '') return;

        $hits = [];
        foreach ($this->profanityList() as $w) {
            if ($w === '') continue;
            if (mb_stripos($text, $w) !== false) $hits[] = $w;
        }
        if (!$hits) return;

        try {
            SecondHandMessageModerationLog::query()->create([
                'conversation_id' => $ctx['conversation_id'] ?? null,
                'listing_id' => $ctx['listing_id'] ?? null,
                'sender_id' => (int) ($ctx['sender_id'] ?? 0),
                'receiver_id' => $ctx['receiver_id'] ?? null,
                'body' => (string) ($body ?? ''),
                'reason' => 'profanity',
                'matched' => implode(',', array_slice($hits, 0, 20)),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // log hatası mesajlaşmayı bozmasın
        }

        abort(422, 'Ayıp/kusurlu/argo kelime kullandığınız için gönderemiyoruz.');
    }
    private function storeMessageAttachments(SecondHandMessage $message, Request $request): array
    {
        $out = [];
        $files = $request->file('attachments');
        if (!$files) return $out;

        $files = is_array($files) ? $files : [$files];
        foreach ($files as $file) {
            if (!$file) continue;
            $mime = (string) ($file->getMimeType() ?: '');
            $kind = str_starts_with($mime, 'image/') ? 'image' : 'file';
            $path = $file->store('uploads/second-hand/messages', 'public');

            $att = SecondHandMessageAttachment::query()->create([
                'message_id' => (int) $message->id,
                'kind' => $kind,
                'path' => $path,
                'original_name' => (string) $file->getClientOriginalName(),
                'mime' => $mime,
                'size' => (int) ($file->getSize() ?: 0),
            ]);
            $out[] = $att;
        }
        return $out;
    }

    private function ensureSecondHandVerified(int $userId): void
    {
        $ok = SecondHandVerification::query()
            ->where('user_id', $userId)
            ->where('status', SecondHandVerification::STATUS_APPROVED)
            ->exists();

        abort_unless($ok, 403, 'Mesajlaşma için hesabınızı doğrulamanız gerekiyor.');
    }

    public function inbox(Request $request)
    {
        $user = Auth::guard('api')->user();
        $userId = (int) $user->id;
        $this->ensureSecondHandVerified($userId);

        // `latestOfMany` eager-load (lastMessage) MySQL'de "ambiguous conversation_id" hatası üretebiliyor.
        // Bu yüzden son mesaj bilgilerini subquery ile çekiyoruz (daha hızlı + daha stabil).
        $lastBodySub = SecondHandMessage::query()
            ->select('body')
            ->whereColumn('conversation_id', 'second_hand_conversations.id')
            ->orderByDesc('id')
            ->limit(1);
        $lastSenderSub = SecondHandMessage::query()
            ->select('sender_id')
            ->whereColumn('conversation_id', 'second_hand_conversations.id')
            ->orderByDesc('id')
            ->limit(1);
        // MariaDB bazı sürümler IN (subquery + LIMIT) desteklemez.
        // Bu yüzden scalar subquery ile "son mesaj id" üzerinden kontrol ediyoruz.
        $lastHasAttachmentSub = SecondHandMessageAttachment::query()
            ->selectRaw('1')
            ->whereRaw(
                'message_id = (select id from second_hand_messages where conversation_id = second_hand_conversations.id order by id desc limit 1)'
            )
            ->limit(1);

        $conversations = SecondHandConversation::query()
            ->select('second_hand_conversations.*')
            ->selectSub($lastBodySub, 'last_message_body')
            ->selectSub($lastSenderSub, 'last_message_sender_id')
            ->selectSub($lastHasAttachmentSub, 'last_message_has_attachment')
            ->where(function ($q) use ($user) {
                $q->where('seller_id', $user->id)->orWhere('buyer_id', $user->id);
            })
            ->withCount([
                'messages as unread_count' => function ($q) use ($user) {
                    $q->where('sender_id', '!=', (int) $user->id)
                        ->whereNull('read_at');
                },
            ])
            ->with([
                'listing:id,title,status,user_id,price,condition,published_at',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $allUserIds = $conversations->getCollection()
            ->flatMap(function ($c) {
                return [(int) $c->seller_id, (int) $c->buyer_id];
            })
            ->unique()
            ->values()
            ->all();

        $businessNames = SecondHandVerification::query()
            ->whereIn('user_id', $allUserIds)
            ->where('status', SecondHandVerification::STATUS_APPROVED)
            ->pluck('business_name', 'user_id');

        $conversations->getCollection()->transform(function ($conversation) {
            $body = trim((string) ($conversation->last_message_body ?? ''));
            $hasAtt = !empty($conversation->last_message_has_attachment);
            $conversation->setAttribute(
                'last_message_preview',
                $body !== '' ? Str::limit($body, 120) : ($hasAtt ? '📎 Ek' : null)
            );

            return $conversation;
        });

        $conversations->getCollection()->transform(function ($conversation) use ($userId, $businessNames) {
            $isSeller = (int) $conversation->seller_id === $userId;
            $otherId = $isSeller ? (int) $conversation->buyer_id : (int) $conversation->seller_id;
            $otherRole = $isSeller ? 'buyer' : 'seller';

            $otherDisplay = $otherRole === 'seller'
                ? (string) ($businessNames[$otherId] ?? 'Satıcı')
                : 'Alıcı';

            $lastSenderId = (int) ($conversation->last_message_sender_id ?? 0);
            $lastSenderRole = $lastSenderId === (int) $conversation->seller_id ? 'seller' : 'buyer';
            $lastSenderDisplay = $lastSenderRole === 'seller'
                ? (string) ($businessNames[(int) $conversation->seller_id] ?? 'Satıcı')
                : 'Alıcı';

            $conversation->setAttribute('counterparty_id', $otherId);
            $conversation->setAttribute('counterparty_role', $otherRole);
            $conversation->setAttribute('counterparty_display', $otherDisplay);
            $conversation->setAttribute('seller_business_name', (string) ($businessNames[(int) $conversation->seller_id] ?? ''));
            $conversation->setAttribute('last_message_sender_role', $lastSenderId ? $lastSenderRole : null);
            $conversation->setAttribute('last_message_sender_display', $lastSenderId ? $lastSenderDisplay : null);

            return $conversation;
        });

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    public function messages(Request $request, $conversationId)
    {
        $user = Auth::guard('api')->user();
        $userId = (int) $user->id;
        $this->ensureSecondHandVerified($userId);

        $conversation = SecondHandConversation::query()
            ->with([
                'listing:id,title,status,user_id,price,condition,published_at',
            ])
            ->findOrFail((int) $conversationId);

        abort_unless(
            (int) $conversation->seller_id === (int) $user->id || (int) $conversation->buyer_id === (int) $user->id,
            403,
            'Bu konuşmaya erişim yok.'
        );

        $businessNames = SecondHandVerification::query()
            ->whereIn('user_id', [(int) $conversation->seller_id, (int) $conversation->buyer_id])
            ->where('status', SecondHandVerification::STATUS_APPROVED)
            ->pluck('business_name', 'user_id');

        $isSeller = (int) $conversation->seller_id === $userId;
        $otherId = $isSeller ? (int) $conversation->buyer_id : (int) $conversation->seller_id;
        $otherRole = $isSeller ? 'buyer' : 'seller';
        $otherDisplay = $otherRole === 'seller'
            ? (string) ($businessNames[$otherId] ?? 'Satıcı')
            : ('Alıcı #' . $otherId);

        $conversation->setAttribute('counterparty_id', $otherId);
        $conversation->setAttribute('counterparty_role', $otherRole);
        $conversation->setAttribute('counterparty_display', $otherDisplay);
        $conversation->setAttribute('seller_business_name', (string) ($businessNames[(int) $conversation->seller_id] ?? ''));

        // Konuşmayı açınca, karşı taraftan gelen okunmamış mesajları okundu işaretle
        SecondHandMessage::query()
            ->where('conversation_id', (int) $conversation->id)
            ->where('sender_id', '!=', (int) $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = SecondHandMessage::query()
            ->where('conversation_id', (int) $conversation->id)
            ->with(['attachments:id,message_id,kind,path,original_name,mime,size'])
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        // UI tarafında kolaylık için artan sırada dönelim
        $messages->setCollection($messages->getCollection()->reverse()->values());

        $sellerName = (string) ($businessNames[(int) $conversation->seller_id] ?? 'Satıcı');
        $buyerName = 'Alıcı';
        $messages->getCollection()->transform(function ($m) use ($conversation, $sellerName, $buyerName) {
            $senderRole = (int) $m->sender_id === (int) $conversation->seller_id ? 'seller' : 'buyer';
            $m->setAttribute('sender_role', $senderRole);
            $m->setAttribute('sender_display', $senderRole === 'seller' ? $sellerName : $buyerName);
            return $m;
        });

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function markRead(Request $request, $conversationId)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $conversation = SecondHandConversation::query()->findOrFail((int) $conversationId);

        abort_unless(
            (int) $conversation->seller_id === (int) $user->id || (int) $conversation->buyer_id === (int) $user->id,
            403,
            'Bu konuşmaya erişim yok.'
        );

        $updated = SecondHandMessage::query()
            ->where('conversation_id', (int) $conversation->id)
            ->where('sender_id', '!=', (int) $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Okundu olarak işaretlendi.',
            'updated' => (int) $updated,
        ]);
    }

    public function sendToListing(Request $request, $listingId)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $request->validate([
            // Metin veya eklerden en az biri zorunlu
            'body' => ['nullable', 'string', 'max:2000', 'required_without:attachments'],
            'attachments' => ['nullable'],
            // Mobilde HEIC/HEIF gelebilir
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,heic,heif'],
        ]);

        $listing = SecondHandListing::query()
            ->where('id', (int) $listingId)
            ->with('user:id,email')
            ->firstOrFail();

        if ($listing->status !== SecondHandListing::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Bu ilan için mesajlaşma kapalı.',
            ], 422);
        }

        $sellerId = (int) $listing->user_id;
        $buyerId = (int) $user->id;

        if ($sellerId === $buyerId) {
            return response()->json([
                'message' => 'Kendi ilanınıza mesaj gönderemezsiniz.',
            ], 422);
        }

        // İlan sahibinin de doğrulanmış olması şart
        $this->ensureSecondHandVerified($sellerId);
        $this->ensureNotBlocked($buyerId, $sellerId);

        $ctx = [
            'conversation_id' => null,
            'listing_id' => (int) $listing->id,
            'sender_id' => $buyerId,
            'receiver_id' => $sellerId,
            'body' => (string) ($request->input('body') ?? ''),
        ];
        $this->enforceRateLimitOrAbort($ctx);
        $this->enforceDuplicateOrAbort((string) ($request->input('body') ?? ''), $ctx);
        $this->checkProfanityOrAbort((string) ($request->input('body') ?? ''), [
            'conversation_id' => null,
            'listing_id' => (int) $listing->id,
            'sender_id' => $buyerId,
            'receiver_id' => $sellerId,
        ]);

        $payload = DB::transaction(function () use ($listing, $sellerId, $buyerId, $request) {
            $conversation = SecondHandConversation::query()
                ->where('listing_id', (int) $listing->id)
                ->where('buyer_id', $buyerId)
                ->first();

            $isFirstMessage = false;
            if (!$conversation) {
                $conversation = SecondHandConversation::query()->create([
                    'listing_id' => (int) $listing->id,
                    'seller_id' => $sellerId,
                    'buyer_id' => $buyerId,
                    'last_message_at' => now(),
                ]);
                $isFirstMessage = true;
            } else {
                $conversation->last_message_at = now();
                $conversation->save();
            }

            $message = SecondHandMessage::query()->create([
                'conversation_id' => (int) $conversation->id,
                'sender_id' => $buyerId,
                'body' => (string) ($request->input('body') ?? ''),
            ]);

            $attachments = $this->storeMessageAttachments($message, $request);

            $conversation->load([
                'listing:id,title,status,user_id,price,condition,published_at',
                'seller:id,name',
                'buyer:id,name',
            ]);

            return [
                'conversation' => $conversation,
                'message' => $message,
                'attachments' => $attachments,
                'is_first' => $isFirstMessage,
            ];
        });

        // Realtime bildirim (satıcıya)
        try {
            $receiver = \App\Models\User::query()->find($sellerId);
            if ($receiver) {
                $sellerBusinessName = (string) SecondHandVerification::query()
                    ->where('user_id', $sellerId)
                    ->where('status', SecondHandVerification::STATUS_APPROVED)
                    ->value('business_name');
                $buyerDisplay = 'Alıcı';
                Event::dispatch(new SecondHandMessageSent([
                    'conversation_id' => (int) $payload['conversation']->id,
                    'listing_id' => (int) $listing->id,
                    'listing_title' => (string) $listing->title,
                    'body' => (string) $request->input('body'),
                    'sender_id' => (int) $buyerId,
                    'sender_role' => 'buyer',
                    'sender_display' => $buyerDisplay,
                    'seller_business_name' => $sellerBusinessName,
                    'attachments' => collect($payload['attachments'] ?? [])->map(function ($a) {
                        return [
                            'id' => (int) $a->id,
                            'kind' => (string) $a->kind,
                            'path' => (string) $a->path,
                            'original_name' => (string) ($a->original_name ?? ''),
                            'mime' => (string) ($a->mime ?? ''),
                            'size' => (int) ($a->size ?? 0),
                        ];
                    })->values()->all(),
                    'type' => 'incoming',
                ], $receiver));
            }
        } catch (\Throwable $e) {
            // bildirim hatası mesajlaşmayı bozmasın
        }

        // İlk mesajda e-posta bildirimi (satıcıya)
        if (!empty($payload['is_first']) && $listing->user && !empty($listing->user->email)) {
            $base = rtrim((string) env('FRONTEND_URL', ''), '/');
            $inboxUrl = $base ? ($base.'/profile#second-hand-messages') : '/profile#second-hand-messages';
            try {
                Mail::to($listing->user->email)->send(
                    new SecondHandFirstMessageMail(
                        (string) $listing->title,
                        (string) $request->input('body'),
                        (string) $inboxUrl
                    )
                );
            } catch (\Throwable $e) {
                // Mail hatası mesajlaşmayı bozmasın.
            }
        }

        return response()->json([
            'message' => 'Mesaj gönderildi.',
            'conversation' => $payload['conversation'],
            'sent' => $payload['message'],
            'attachments' => collect($payload['attachments'] ?? [])->values(),
        ], 201);
    }

    public function sendToConversation(Request $request, $conversationId)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $request->validate([
            // Metin veya eklerden en az biri zorunlu
            'body' => ['nullable', 'string', 'max:2000', 'required_without:attachments'],
            'attachments' => ['nullable'],
            // Mobilde HEIC/HEIF gelebilir
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,heic,heif'],
        ]);

        $conversation = SecondHandConversation::query()
            ->with('listing:id,status,user_id')
            ->findOrFail((int) $conversationId);

        abort_unless(
            (int) $conversation->seller_id === (int) $user->id || (int) $conversation->buyer_id === (int) $user->id,
            403,
            'Bu konuşmaya erişim yok.'
        );

        // İlan active değilse mesaj kapalı
        if (!$conversation->listing || $conversation->listing->status !== SecondHandListing::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Bu ilan için mesajlaşma kapalı.',
            ], 422);
        }

        // Her iki taraf doğrulanmış olmalı
        $this->ensureSecondHandVerified((int) $conversation->seller_id);
        $this->ensureSecondHandVerified((int) $conversation->buyer_id);

        $senderId = (int) $user->id;
        $receiverId = (int) ($senderId === (int) $conversation->seller_id ? $conversation->buyer_id : $conversation->seller_id);
        $this->ensureNotBlocked($senderId, $receiverId);
        $ctx = [
            'conversation_id' => (int) $conversation->id,
            'listing_id' => (int) ($conversation->listing_id ?? 0),
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'body' => (string) ($request->input('body') ?? ''),
        ];
        $this->enforceRateLimitOrAbort($ctx);
        $this->enforceDuplicateOrAbort((string) ($request->input('body') ?? ''), $ctx);
        $this->checkProfanityOrAbort((string) ($request->input('body') ?? ''), $ctx);

        $payload = DB::transaction(function () use ($conversation, $user, $request) {
            $msg = SecondHandMessage::query()->create([
                'conversation_id' => (int) $conversation->id,
                'sender_id' => (int) $user->id,
                'body' => (string) ($request->input('body') ?? ''),
            ]);

            $attachments = $this->storeMessageAttachments($msg, $request);

            $conversation->last_message_at = now();
            $conversation->save();

            return ['message' => $msg, 'attachments' => $attachments];
        });

        // Realtime bildirim (karşı tarafa)
        try {
            $senderId = (int) $user->id;
            $receiverId = (int) ($senderId === (int) $conversation->seller_id ? $conversation->buyer_id : $conversation->seller_id);
            $receiver = \App\Models\User::query()->find($receiverId);
            if ($receiver && $conversation->listing) {
                $sellerBusinessName = (string) SecondHandVerification::query()
                    ->where('user_id', (int) $conversation->seller_id)
                    ->where('status', SecondHandVerification::STATUS_APPROVED)
                    ->value('business_name');
                $buyerDisplay = 'Alıcı';
                $senderRole = $senderId === (int) $conversation->seller_id ? 'seller' : 'buyer';
                $senderDisplay = $senderRole === 'seller' ? ($sellerBusinessName ?: 'Satıcı') : $buyerDisplay;
                Event::dispatch(new SecondHandMessageSent([
                    'conversation_id' => (int) $conversation->id,
                    'listing_id' => (int) $conversation->listing->id,
                    'listing_title' => (string) ($conversation->listing->title ?? 'İlan'),
                    'body' => (string) $request->input('body'),
                    'sender_id' => $senderId,
                    'sender_role' => $senderRole,
                    'sender_display' => $senderDisplay,
                    'seller_business_name' => $sellerBusinessName,
                    'attachments' => collect($payload['attachments'] ?? [])->map(function ($a) {
                        return [
                            'id' => (int) $a->id,
                            'kind' => (string) $a->kind,
                            'path' => (string) $a->path,
                            'original_name' => (string) ($a->original_name ?? ''),
                            'mime' => (string) ($a->mime ?? ''),
                            'size' => (int) ($a->size ?? 0),
                        ];
                    })->values()->all(),
                    'type' => 'incoming',
                ], $receiver));
            }
        } catch (\Throwable $e) {
            // bildirim hatası mesajlaşmayı bozmasın
        }

        return response()->json([
            'message' => 'Mesaj gönderildi.',
            'sent' => $payload['message'],
            'attachments' => collect($payload['attachments'] ?? [])->values(),
        ], 201);
    }
}

