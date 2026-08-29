<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalonCrmAppointment;
use App\Models\SalonCrmSalon;
use App\Services\SalonCrmAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SalonCrmAdminController extends Controller
{
    public function __construct(private SalonCrmAccessService $accessService)
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('salon_crm_salons')) {
            return view('admin.salon_crm.index', [
                'migrationMissing' => true,
                'stats' => [],
                'salons' => collect(),
            ]);
        }

        $now = Carbon::now();
        $query = SalonCrmSalon::query()
            ->with('user:id,name,email,phone')
            ->withCount(['staff', 'customers', 'appointments', 'services'])
            ->orderByDesc('id');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")
                    ->orWhere('owner_username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($filter = $request->input('filter')) {
            match ($filter) {
                'unlocked' => $query->where(function ($q) use ($now) {
                    $q->where('trial_ends_at', '>=', $now)
                        ->orWhere('admin_free_until', '>=', $now);
                }),
                'locked' => $query->where(function ($q) use ($now) {
                    $q->where(function ($sq) use ($now) {
                        $sq->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<', $now);
                    })->where(function ($sq) use ($now) {
                        $sq->whereNull('admin_free_until')->orWhere('admin_free_until', '<', $now);
                    });
                }),
                'admin_free' => $query->where('admin_free_until', '>=', $now),
                'trial' => $query->where('trial_ends_at', '>=', $now),
                default => null,
            };
        }

        $salons = $query->paginate(25)->withQueryString();
        $salons->getCollection()->transform(function (SalonCrmSalon $salon) {
            $salon->access_snapshot = $this->accessService->snapshot($salon, $salon->user);
            $salon->days_active = $salon->created_at
                ? $salon->created_at->diffInDays(now()) + 1
                : 0;

            return $salon;
        });

        $stats = $this->globalStats();

        return view('admin.salon_crm.index', compact('salons', 'stats'));
    }

    public function show(int $id)
    {
        if (!Schema::hasTable('salon_crm_salons')) {
            return redirect()->route('admin.salon-crm.index');
        }

        $salon = SalonCrmSalon::query()
            ->with([
                'user:id,name,email,phone,created_at',
                'staff' => fn ($q) => $q->orderBy('name'),
                'grants' => fn ($q) => $q->orderByDesc('id')->limit(20),
            ])
            ->withCount(['staff', 'customers', 'appointments', 'services'])
            ->findOrFail($id);

        $snapshot = $this->accessService->snapshot($salon, $salon->user);
        $monthSpend = $salon->user
            ? $this->accessService->monthSpend($salon->user->id, Carbon::now())
            : 0;

        $recentAppointments = SalonCrmAppointment::query()
            ->with(['staff:id,name', 'service:id,name'])
            ->where('salon_id', $salon->id)
            ->where('is_block', false)
            ->orderByDesc('starts_at')
            ->limit(15)
            ->get();

        $appointmentStats = [
            'total' => $salon->appointments_count,
            'this_month' => SalonCrmAppointment::query()
                ->where('salon_id', $salon->id)
                ->where('is_block', false)
                ->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'scheduled' => SalonCrmAppointment::query()
                ->where('salon_id', $salon->id)
                ->where('status', 'scheduled')
                ->count(),
            'completed' => SalonCrmAppointment::query()
                ->where('salon_id', $salon->id)
                ->where('status', 'completed')
                ->count(),
        ];

        $daysActive = $salon->created_at
            ? $salon->created_at->diffInDays(now()) + 1
            : 0;

        return view('admin.salon_crm.show', compact(
            'salon',
            'snapshot',
            'monthSpend',
            'recentAppointments',
            'appointmentStats',
            'daysActive'
        ));
    }

    public function updateAccess(Request $request, int $id)
    {
        $salon = SalonCrmSalon::query()->findOrFail($id);

        $data = $request->validate([
            'action' => ['required', 'in:free_1m,free_3m,free_6m,free_12m,free_forever,remove_free,extend_trial_30,extend_trial_90,custom_date,save_notes'],
            'admin_free_until' => ['nullable', 'date'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $message = 'Güncellendi.';

        switch ($data['action']) {
            case 'free_1m':
                $salon->admin_free_until = now()->addMonth();
                $message = '1 ay ücretsiz CRM erişimi verildi.';
                break;
            case 'free_3m':
                $salon->admin_free_until = now()->addMonths(3);
                $message = '3 ay ücretsiz CRM erişimi verildi.';
                break;
            case 'free_6m':
                $salon->admin_free_until = now()->addMonths(6);
                $message = '6 ay ücretsiz CRM erişimi verildi.';
                break;
            case 'free_12m':
                $salon->admin_free_until = now()->addYear();
                $message = '12 ay ücretsiz CRM erişimi verildi.';
                break;
            case 'free_forever':
                $salon->admin_free_until = Carbon::parse('2099-12-31 23:59:59');
                $message = 'Süresiz ücretsiz CRM erişimi verildi.';
                break;
            case 'remove_free':
                $salon->admin_free_until = null;
                $message = 'Admin ücretsiz erişimi kaldırıldı.';
                break;
            case 'extend_trial_30':
                $base = $salon->trial_ends_at && $salon->trial_ends_at->isFuture()
                    ? $salon->trial_ends_at
                    : now();
                $salon->trial_ends_at = $base->copy()->addDays(30);
                $message = 'Deneme süresi 30 gün uzatıldı.';
                break;
            case 'extend_trial_90':
                $base = $salon->trial_ends_at && $salon->trial_ends_at->isFuture()
                    ? $salon->trial_ends_at
                    : now();
                $salon->trial_ends_at = $base->copy()->addDays(90);
                $message = 'Deneme süresi 90 gün uzatıldı.';
                break;
            case 'custom_date':
                if (empty($data['admin_free_until'])) {
                    return redirect()->back()->with([
                        'messege' => 'Ücretsiz bitiş tarihi gerekli.',
                        'alert-type' => 'error',
                    ]);
                }
                $salon->admin_free_until = Carbon::parse($data['admin_free_until'])->endOfDay();
                $message = 'Ücretsiz erişim tarihi güncellendi.';
                break;
            case 'save_notes':
                $message = 'Not kaydedildi.';
                break;
        }

        if (array_key_exists('admin_notes', $data)) {
            $salon->admin_notes = $data['admin_notes'];
        }

        $salon->save();

        return redirect()->route('admin.salon-crm.show', $salon->id)->with([
            'messege' => $message,
            'alert-type' => 'success',
        ]);
    }

    private function globalStats(): array
    {
        $now = Carbon::now();
        $total = SalonCrmSalon::query()->count();
        $adminFree = SalonCrmSalon::query()->where('admin_free_until', '>=', $now)->count();
        $inTrial = SalonCrmSalon::query()->where('trial_ends_at', '>=', $now)->count();

        return [
            'total_salons' => $total,
            'admin_free' => $adminFree,
            'in_trial' => $inTrial,
            'total_staff' => Schema::hasTable('salon_crm_staff')
                ? \App\Models\SalonCrmStaff::query()->count()
                : 0,
            'total_customers' => Schema::hasTable('salon_crm_customers')
                ? \App\Models\SalonCrmCustomer::query()->count()
                : 0,
            'appointments_this_month' => Schema::hasTable('salon_crm_appointments')
                ? SalonCrmAppointment::query()
                    ->where('is_block', false)
                    ->whereBetween('starts_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                    ->count()
                : 0,
            'total_appointments' => Schema::hasTable('salon_crm_appointments')
                ? SalonCrmAppointment::query()->where('is_block', false)->count()
                : 0,
        ];
    }
}
