<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SecondHandConversation;
use App\Models\SecondHandListing;
use App\Models\SecondHandMessage;
use App\Models\SecondHandReport;
use App\Models\SecondHandVerification;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecondHandReportController extends Controller
{
    private function ensureSecondHandVerified(int $userId): void
    {
        $ok = SecondHandVerification::query()
            ->where('user_id', $userId)
            ->where('status', SecondHandVerification::STATUS_APPROVED)
            ->exists();

        abort_unless($ok, 403, 'Rapor göndermek için hesabınızı doğrulamanız gerekiyor.');
    }

    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();
        $this->ensureSecondHandVerified((int) $user->id);

        $request->validate([
            'subject_type' => ['required', 'in:listing,message,user'],
            'subject_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'in:spam,scam,harassment,illegal,other'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $subjectType = (string) $request->input('subject_type');
        $subjectId = (int) $request->input('subject_id');

        $listingId = null;
        $conversationId = null;
        $messageId = null;

        if ($subjectType === SecondHandReport::SUBJECT_LISTING) {
            $listing = SecondHandListing::query()->findOrFail($subjectId);
            $listingId = (int) $listing->id;
        } elseif ($subjectType === SecondHandReport::SUBJECT_MESSAGE) {
            $message = SecondHandMessage::query()->with('conversation')->findOrFail($subjectId);
            $conversation = $message->conversation;
            abort_unless($conversation, 404);

            abort_unless(
                (int) $conversation->seller_id === (int) $user->id || (int) $conversation->buyer_id === (int) $user->id,
                403,
                'Bu mesajı raporlama yetkiniz yok.'
            );

            $messageId = (int) $message->id;
            $conversationId = (int) $conversation->id;
            $listingId = (int) $conversation->listing_id;
        } elseif ($subjectType === SecondHandReport::SUBJECT_USER) {
            // subject_id = reported user id
            abort_if($subjectId === (int) $user->id, 422, 'Kendinizi raporlayamazsınız.');
        }

        try {
            $report = SecondHandReport::query()->create([
                'reporter_user_id' => (int) $user->id,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'listing_id' => $listingId,
                'conversation_id' => $conversationId,
                'message_id' => $messageId,
                'reason' => (string) $request->input('reason'),
                'details' => $request->input('details'),
                'status' => SecondHandReport::STATUS_OPEN,
            ]);
        } catch (QueryException $e) {
            // uniq_second_hand_report_subject
            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Bu konu için zaten bir rapor göndermişsiniz.',
                ], 409);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Rapor alındı. Teşekkürler.',
            'report' => $report,
        ], 201);
    }
}
