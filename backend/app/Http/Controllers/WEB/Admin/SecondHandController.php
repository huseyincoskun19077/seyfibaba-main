<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecondHandListing;
use App\Models\SecondHandConversation;
use App\Models\SecondHandMessage;
use App\Models\SecondHandMessageAttachment;
use App\Models\SecondHandMessageModerationLog;
use App\Models\SecondHandReport;
use App\Models\SecondHandUserBlock;
use App\Models\SecondHandVerification;
use App\Models\SecondHandAgreement;
use App\Models\SecondHandSlider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SecondHandController extends Controller
{
    public function index()
    {
        $stats = [
            'active' => SecondHandListing::where('status', SecondHandListing::STATUS_ACTIVE)->count(),
            'pending' => SecondHandListing::where('status', SecondHandListing::STATUS_PENDING)->count(),
            'featured' => SecondHandListing::where('is_featured', 1)->count(),
            'urgent' => SecondHandListing::where('is_urgent', 1)->count(),
            'views' => (int) SecondHandListing::sum('views_count'),
        ];
        $topViewed = SecondHandListing::query()
            ->where('status', SecondHandListing::STATUS_ACTIVE)
            ->orderByDesc('views_count')
            ->limit(10)
            ->get(['id', 'title', 'views_count', 'price', 'is_featured', 'is_urgent', 'published_at']);

        return view('admin.second_hand.index', compact('stats', 'topViewed'));
    }

    public function agreements()
    {
        $agreement = SecondHandAgreement::query()->firstOrCreate(
            ['id' => 1],
            [
                'terms_title' => 'İkinci El Kullanım Koşulları',
                'terms_content' => "Bu metni admin panelinden düzenleyebilirsiniz.",
                'privacy_title' => 'İkinci El KVKK / Gizlilik Metni',
                'privacy_content' => "Bu metni admin panelinden düzenleyebilirsiniz.",
            ]
        );

        return view('admin.second_hand.agreements', compact('agreement'));
    }

    public function updateAgreements(Request $request)
    {
        $request->validate([
            'terms_title' => ['required', 'string', 'max:255'],
            'terms_content' => ['required', 'string'],
            'privacy_title' => ['required', 'string', 'max:255'],
            'privacy_content' => ['required', 'string'],
        ]);

        $agreement = SecondHandAgreement::query()->firstOrCreate(['id' => 1]);
        $agreement->terms_title = trim((string) $request->input('terms_title'));
        $agreement->terms_content = (string) $request->input('terms_content');
        $agreement->privacy_title = trim((string) $request->input('privacy_title'));
        $agreement->privacy_content = (string) $request->input('privacy_content');
        $agreement->updated_by = Auth::guard('admin')->id();
        $agreement->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function homepage(Request $request)
    {
        $agreement = SecondHandAgreement::query()->firstOrCreate(
            ['id' => 1],
            [
                'terms_title' => 'İkinci El Kullanım Koşulları',
                'terms_content' => 'Bu metni admin panelinden düzenleyebilirsiniz.',
                'privacy_title' => 'İkinci El KVKK / Gizlilik Metni',
                'privacy_content' => 'Bu metni admin panelinden düzenleyebilirsiniz.',
                'homepage_title' => 'Kuaför malzemeleri al/sat',
                'homepage_subtitle' => 'Doğrulanmış satıcılardan ikinci el ekipman. İlanlara herkes bakabilir; teklif ve mesaj için üye girişi gerekir.',
                'homepage_cta_primary' => 'İlan ver',
                'homepage_cta_secondary' => 'İlanları gör',
                'homepage_show_categories' => true,
                'homepage_show_featured' => true,
            ]
        );

        $sliders = Schema::hasTable('second_hand_sliders')
            ? SecondHandSlider::query()->orderBy('serial')->orderBy('id')->get()
            : collect();
        $editSlider = null;
        if ($request->filled('edit_slider') && Schema::hasTable('second_hand_sliders')) {
            $editSlider = SecondHandSlider::query()->find((int) $request->input('edit_slider'));
        }

        return view('admin.second_hand.homepage', compact('agreement', 'sliders', 'editSlider'));
    }

    public function updateHomepage(Request $request)
    {
        $request->validate([
            'homepage_title' => ['required', 'string', 'max:255'],
            'homepage_subtitle' => ['nullable', 'string', 'max:2000'],
            'homepage_cta_primary' => ['nullable', 'string', 'max:80'],
            'homepage_cta_secondary' => ['nullable', 'string', 'max:80'],
            'homepage_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $agreement = SecondHandAgreement::query()->firstOrCreate(['id' => 1]);
        $agreement->homepage_title = trim((string) $request->input('homepage_title'));
        $agreement->homepage_subtitle = trim((string) $request->input('homepage_subtitle', ''));
        $agreement->homepage_cta_primary = trim((string) $request->input('homepage_cta_primary', '')) ?: 'İlan ver';
        $agreement->homepage_cta_secondary = trim((string) $request->input('homepage_cta_secondary', '')) ?: 'İlanları gör';
        $agreement->homepage_show_categories = $request->boolean('homepage_show_categories');
        $agreement->homepage_show_featured = $request->boolean('homepage_show_featured');

        if ($request->boolean('homepage_image_remove') && $agreement->homepage_image) {
            if (File::exists(public_path($agreement->homepage_image))) {
                File::delete(public_path($agreement->homepage_image));
            }
            $agreement->homepage_image = null;
        }

        if ($request->hasFile('homepage_image')) {
            $file = $request->file('homepage_image');
            $name = 'second-hand-home-'.date('Y-m-d-His').'-'.rand(1000, 9999).'.'.$file->getClientOriginalExtension();
            $rel = 'uploads/website-images/'.$name;
            $file->move(public_path('uploads/website-images'), $name);
            if ($agreement->homepage_image && File::exists(public_path($agreement->homepage_image))) {
                File::delete(public_path($agreement->homepage_image));
            }
            $agreement->homepage_image = $rel;
        }

        $agreement->updated_by = Auth::guard('admin')->id();
        $agreement->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function storeHomepageSlider(Request $request)
    {
        $request->validate([
            'image' => [$request->filled('id') ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:500'],
            'serial' => ['nullable', 'integer', 'min:0'],
        ]);

        $slider = $request->filled('id')
            ? SecondHandSlider::query()->findOrFail((int) $request->input('id'))
            : new SecondHandSlider();

        $slider->title = trim((string) $request->input('title', ''));
        $slider->subtitle = trim((string) $request->input('subtitle', ''));
        $slider->link = trim((string) $request->input('link', ''));
        $slider->serial = (int) ($request->input('serial') ?: 1);
        $slider->status = $request->boolean('status');

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $name = 'second-hand-slider-'.date('Y-m-d-His').'-'.rand(1000, 9999).'.'.$file->getClientOriginalExtension();
            $rel = 'uploads/website-images/'.$name;
            $file->move(public_path('uploads/website-images'), $name);
            if ($slider->image && File::exists(public_path($slider->image))) {
                File::delete(public_path($slider->image));
            }
            $slider->image = $rel;
        }

        $slider->save();

        return redirect()->route('admin.second-hand.homepage')->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function deleteHomepageSlider($id)
    {
        $slider = SecondHandSlider::query()->findOrFail((int) $id);
        if ($slider->image && File::exists(public_path($slider->image))) {
            File::delete(public_path($slider->image));
        }
        $slider->delete();

        return redirect()->route('admin.second-hand.homepage')->with([
            'messege' => 'Silindi',
            'alert-type' => 'success',
        ]);
    }

    public function members(Request $request)
    {
        $query = SecondHandVerification::query()
            ->select('second_hand_verifications.*')
            ->selectSub(
                SecondHandListing::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('second_hand_listings.user_id', 'second_hand_verifications.user_id'),
                'listings_total'
            )
            ->selectSub(
                SecondHandListing::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('second_hand_listings.user_id', 'second_hand_verifications.user_id')
                    ->where('second_hand_listings.status', SecondHandListing::STATUS_ACTIVE),
                'listings_active'
            )
            ->with(['user:id,name,email', 'reviewer:id,name,email']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($qq) use ($q) {
                $qq->where('business_name', 'like', '%'.$q.'%')
                    ->orWhere('tax_number', 'like', '%'.$q.'%')
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', '%'.$q.'%')
                            ->orWhere('email', 'like', '%'.$q.'%');
                    });
            });
        }

        $rows = $query->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.second_hand.members', [
            'rows' => $rows,
        ]);
    }

    public function verifications(Request $request)
    {
        $query = SecondHandVerification::query()->with(['user:id,name,email', 'reviewer:id,name,email']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $verifications = $query->orderByRaw("FIELD(status, 'pending', 'rejected', 'approved')")
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.second_hand.verifications', compact('verifications'));
    }

    public function listings(Request $request)
    {
        $query = SecondHandListing::query()
            ->with(['user:id,name,email', 'city:id,name', 'category:id,name'])
            ->select('second_hand_listings.*')
            ->selectSub(
                SecondHandConversation::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('second_hand_conversations.listing_id', 'second_hand_listings.id'),
                'conversations_count'
            )
            ->selectSub(
                SecondHandMessage::query()
                    ->selectRaw('count(*)')
                    ->join('second_hand_conversations', 'second_hand_conversations.id', '=', 'second_hand_messages.conversation_id')
                    ->whereColumn('second_hand_conversations.listing_id', 'second_hand_listings.id'),
                'messages_count'
            )
            ->selectSub(
                SecondHandConversation::query()
                    ->selectRaw('count(distinct buyer_id)')
                    ->whereColumn('second_hand_conversations.listing_id', 'second_hand_listings.id'),
                'unique_buyers_count'
            )
            ->selectSub(
                SecondHandConversation::query()
                    ->selectRaw('max(last_message_at)')
                    ->whereColumn('second_hand_conversations.listing_id', 'second_hand_listings.id'),
                'last_message_at'
            )
            ->selectSub(
                SecondHandMessage::query()
                    ->selectRaw('second_hand_messages.body')
                    ->join('second_hand_conversations', 'second_hand_conversations.id', '=', 'second_hand_messages.conversation_id')
                    ->whereColumn('second_hand_conversations.listing_id', 'second_hand_listings.id')
                    ->orderByDesc('second_hand_messages.id')
                    ->limit(1),
                'last_message_preview'
            )
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('condition') && $request->condition !== 'all') {
            $query->where('condition', $request->condition);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%');
            });
        }

        if ($request->filled('user_email')) {
            $email = trim((string) $request->user_email);
            $query->whereHas('user', function ($u) use ($email) {
                $u->where('email', 'like', '%'.$email.'%');
            });
        }

        $listings = $query->paginate(30)->withQueryString();

        return view('admin.second_hand.listings', [
            'listings' => $listings,
            'conditionOptions' => SecondHandListing::conditionOptions(),
        ]);
    }

    public function messagesInbox(Request $request)
    {
        $query = SecondHandConversation::query()
            ->with(['listing:id,title,user_id'])
            ->select('second_hand_conversations.*')
            ->selectSub(
                SecondHandMessage::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('second_hand_messages.conversation_id', 'second_hand_conversations.id'),
                'messages_count'
            )
            ->selectSub(
                SecondHandMessage::query()
                    ->selectRaw('second_hand_messages.body')
                    ->whereColumn('second_hand_messages.conversation_id', 'second_hand_conversations.id')
                    ->orderByDesc('second_hand_messages.id')
                    ->limit(1),
                'last_message_preview'
            )
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($request->filled('listing_id')) {
            $query->where('listing_id', (int) $request->listing_id);
        }

        if ($request->filled('user_id')) {
            $uid = (int) $request->user_id;
            $query->where(function ($q) use ($uid) {
                $q->where('seller_id', $uid)->orWhere('buyer_id', $uid);
            });
        }

        $conversations = $query->paginate(40)->withQueryString();

        return view('admin.second_hand.messages_inbox', [
            'conversations' => $conversations,
        ]);
    }

    public function messagesConversation(Request $request, $conversationId)
    {
        $conversation = SecondHandConversation::query()
            ->with(['listing:id,title,user_id'])
            ->findOrFail((int) $conversationId);

        $messages = SecondHandMessage::query()
            ->where('conversation_id', (int) $conversation->id)
            ->with(['attachments:id,message_id,kind,path,original_name,mime,size'])
            ->orderByDesc('id')
            ->paginate(80)
            ->withQueryString();

        // UI'da eskiden yeniye
        $messages->setCollection($messages->getCollection()->reverse()->values());

        return view('admin.second_hand.messages_conversation', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function moderationLogs(Request $request)
    {
        $query = SecondHandMessageModerationLog::query()->orderByDesc('id');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where('body', 'like', '%'.$q.'%');
        }

        $rows = $query->paginate(50)->withQueryString();

        return view('admin.second_hand.moderation_logs', [
            'rows' => $rows,
        ]);
    }

    public function setListingFeatured(Request $request, $id)
    {
        $listing = SecondHandListing::query()->findOrFail($id);
        $listing->is_featured = true;
        $listing->featured_at = now();
        $listing->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function unsetListingFeatured(Request $request, $id)
    {
        $listing = SecondHandListing::query()->findOrFail($id);
        $listing->is_featured = false;
        $listing->featured_at = null;
        $listing->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function setListingUrgent(Request $request, $id)
    {
        $listing = SecondHandListing::query()->findOrFail($id);
        $listing->is_urgent = true;
        $listing->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function unsetListingUrgent(Request $request, $id)
    {
        $listing = SecondHandListing::query()->findOrFail($id);
        $listing->is_urgent = false;
        $listing->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function reports(Request $request)
    {
        $query = SecondHandReport::query()
            ->with([
                'reporter:id,name,email',
                'listing:id,title,status,user_id',
                'handler:id,name,email',
            ])
            ->orderByRaw("FIELD(status, 'open', 'reviewing', 'resolved', 'dismissed')")
            ->orderByDesc('id');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('subject_type') && $request->subject_type !== 'all') {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('reason') && $request->reason !== 'all') {
            $query->where('reason', $request->reason);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($qq) use ($q) {
                $qq->where('details', 'like', '%'.$q.'%')
                    ->orWhere('admin_note', 'like', '%'.$q.'%')
                    ->orWhere('id', $q);
            });
        }

        $reports = $query->paginate(30)->withQueryString();

        return view('admin.second_hand.reports', [
            'reports' => $reports,
        ]);
    }

    public function blocks(Request $request)
    {
        $query = SecondHandUserBlock::query()
            ->with([
                'blocker:id,name,email',
                'blocked:id,name,email',
            ])
            ->orderByDesc('id');

        if ($request->filled('user_id')) {
            $uid = (int) $request->user_id;
            $query->where(function ($q) use ($uid) {
                $q->where('blocker_id', $uid)->orWhere('blocked_id', $uid);
            });
        }

        $blocks = $query->paginate(40)->withQueryString();

        return view('admin.second_hand.blocks', [
            'blocks' => $blocks,
        ]);
    }

    public function approveVerification(Request $request, $id)
    {
        $request->validate([
            'admin_note' => ['nullable', 'string'],
        ]);

        $verification = SecondHandVerification::query()->findOrFail($id);
        $verification->update([
            'status' => SecondHandVerification::STATUS_APPROVED,
            'admin_note' => $request->input('admin_note'),
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
        ]);

        try {
            \App\Helpers\MailHelper::setMailConfig();
            $user = \App\Models\User::find($verification->user_id);
            if ($user && $user->email) {
                $content = "Tebrikler! İkinci el hesabınız onaylandı.\n\nArtık ikinci el ürünlerinizi Seyfibaba üzerinden ilan verebilirsiniz.\n\nİşletme: " . ($verification->business_name ?? $user->name);
                \Mail::to($user->email)->send(new \App\Mail\SecondHandActivatedMail($content));
            }
        } catch (\Throwable $e) {
            \Log::warning('Second hand activation mail failed', ['user_id' => $verification->user_id, 'error' => $e->getMessage()]);
        }

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function rejectVerification(Request $request, $id)
    {
        $request->validate([
            'admin_note' => ['required', 'string'],
        ]);

        $verification = SecondHandVerification::query()->findOrFail($id);
        $verification->update([
            'status' => SecondHandVerification::STATUS_REJECTED,
            'admin_note' => $request->input('admin_note'),
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function downloadVerificationTaxDocument($id)
    {
        $verification = SecondHandVerification::query()->findOrFail($id);

        abort_unless(
            $verification->tax_document_path &&
            Storage::disk('local')->exists($verification->tax_document_path),
            404
        );

        return Storage::disk('local')->download(
            $verification->tax_document_path,
            $verification->tax_document_original_name ?: basename($verification->tax_document_path)
        );
    }

    public function downloadVerificationBarberDocument($id)
    {
        $verification = SecondHandVerification::query()->findOrFail($id);

        abort_unless(
            $verification->barber_document_path &&
            Storage::disk('local')->exists($verification->barber_document_path),
            404
        );

        return Storage::disk('local')->download(
            $verification->barber_document_path,
            $verification->barber_document_original_name ?: basename($verification->barber_document_path)
        );
    }

    public function deactivateListing(Request $request, $id)
    {
        $request->validate([
            'inactive_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $listing = SecondHandListing::query()->findOrFail($id);

        if ($listing->status !== SecondHandListing::STATUS_ACTIVE) {
            return redirect()->back()->with([
                'messege' => 'Sadece yayındaki ilan pasife alınabilir.',
                'alert-type' => 'error',
            ]);
        }

        $listing->status = SecondHandListing::STATUS_INACTIVE;
        $listing->inactive_reason = $request->input('inactive_reason');
        $listing->deactivated_at = now();
        $listing->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function activateListing(Request $request, $id)
    {
        $listing = SecondHandListing::query()->findOrFail($id);

        if ($listing->status !== SecondHandListing::STATUS_INACTIVE) {
            return redirect()->back()->with([
                'messege' => 'Sadece pasif ilan tekrar yayına alınabilir.',
                'alert-type' => 'error',
            ]);
        }

        $listing->status = SecondHandListing::STATUS_ACTIVE;
        $listing->inactive_reason = null;
        $listing->deactivated_at = null;
        $listing->published_at = $listing->published_at ?: now();
        $listing->reviewed_by = Auth::guard('admin')->id();
        $listing->reviewed_at = now();
        $listing->review_note = null;
        $listing->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function approveListing(Request $request, $id)
    {
        $listing = SecondHandListing::query()->findOrFail($id);

        if (! in_array($listing->status, [SecondHandListing::STATUS_PENDING, SecondHandListing::STATUS_INACTIVE], true)) {
            return redirect()->back()->with([
                'messege' => 'Sadece onay bekleyen/pasif ilan onaylanabilir.',
                'alert-type' => 'error',
            ]);
        }

        $listing->status = SecondHandListing::STATUS_ACTIVE;
        $listing->inactive_reason = null;
        $listing->deactivated_at = null;
        $listing->published_at = $listing->published_at ?: now();
        $listing->reviewed_by = Auth::guard('admin')->id();
        $listing->reviewed_at = now();
        $listing->review_note = null;
        $listing->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function rejectListing(Request $request, $id)
    {
        $request->validate([
            'review_note' => ['required', 'string', 'max:500'],
        ]);

        $listing = SecondHandListing::query()->findOrFail($id);

        if ($listing->status !== SecondHandListing::STATUS_PENDING) {
            return redirect()->back()->with([
                'messege' => 'Sadece onay bekleyen ilan reddedilebilir.',
                'alert-type' => 'error',
            ]);
        }

        $listing->status = SecondHandListing::STATUS_REJECTED;
        $listing->inactive_reason = $request->input('review_note');
        $listing->review_note = $request->input('review_note');
        $listing->reviewed_by = Auth::guard('admin')->id();
        $listing->reviewed_at = now();
        $listing->deactivated_at = now();
        $listing->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function updateReportStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:open,reviewing,resolved,dismissed'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = SecondHandReport::query()->findOrFail($id);

        $report->status = (string) $request->input('status');
        $report->admin_note = $request->input('admin_note', $report->admin_note);
        $report->handled_by = Auth::guard('admin')->id();
        $report->handled_at = now();
        $report->save();

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }
}

