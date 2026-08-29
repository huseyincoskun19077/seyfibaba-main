<?php

namespace App\Services\CallCenter;

use App\Models\Admin;
use App\Models\CallCenterCommission;
use App\Models\CallCenterCommissionPayment;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CallCenterCommissionService
{
    public function __construct(
        private CallCenterCommissionCalculator $calculator,
    ) {}

    public function syncForVendor(Vendor $vendor): CallCenterCommission
    {
        if (! $vendor->isCallCenterRegistration() || ! $vendor->registered_by_admin_id) {
            throw new RuntimeException('Bu satıcı çağrı merkezi hakedişine uygun değil.');
        }

        $vendor->loadCount('products');
        $breakdown = $this->calculator->calculate($vendor);

        $commission = CallCenterCommission::query()->firstOrNew([
            'vendor_id' => $vendor->id,
        ]);

        $commission->admin_id = (int) $vendor->registered_by_admin_id;
        $commission->product_count = (int) $breakdown['product_count'];
        $commission->calculated_total = $breakdown['total'];
        $commission->breakdown = $breakdown;

        // Yeni ürün geldiyse ve ödeme beklenmiyorsa açık duruma al
        if (! $commission->isAwaitingPayment()) {
            $commission->status = CallCenterCommission::STATUS_OPEN;
            $commission->approved_amount = null;
            $commission->agent_approved_at = null;
        } elseif ((float) $commission->approved_amount < $commission->pendingApprovalAmount()) {
            // Onaylanmış tutar güncel hesaplamadan düşük — yeni onay gerekir
            $commission->status = CallCenterCommission::STATUS_OPEN;
            $commission->approved_amount = null;
            $commission->agent_approved_at = null;
        }

        $commission->save();

        return $commission->fresh();
    }

    /**
     * @param  iterable<int, Vendor>  $vendors
     */
    public function syncMany(iterable $vendors): void
    {
        foreach ($vendors as $vendor) {
            if ($vendor instanceof Vendor && $vendor->isCallCenterRegistration() && $vendor->registered_by_admin_id) {
                $this->syncForVendor($vendor);
            }
        }
    }

    public function approveByAgent(Vendor $vendor, Admin $agent): CallCenterCommission
    {
        if (! $agent->isCallCenterAgent() && ! $agent->isSuperAdmin()) {
            throw new RuntimeException('Yalnızca çağrı merkezi temsilcisi onaylayabilir.');
        }

        if ((int) $vendor->registered_by_admin_id !== (int) $agent->id && ! $agent->isSuperAdmin()) {
            throw new RuntimeException('Bu satıcı sizin kaydınız değil.');
        }

        $commission = $this->syncForVendor($vendor);
        $pending = $commission->pendingApprovalAmount();

        if ($pending <= 0) {
            throw new RuntimeException('Onaylanacak hakediş tutarı yok.');
        }

        if ($commission->isAwaitingPayment()) {
            throw new RuntimeException('Zaten ödeme bekleyen onaylı hakediş var.');
        }

        $commission->approved_amount = $pending;
        $commission->status = CallCenterCommission::STATUS_AWAITING_PAYMENT;
        $commission->agent_approved_at = now();
        $commission->save();

        return $commission->fresh();
    }

    public function markPaid(Vendor $vendor, Admin $admin, ?string $note = null): CallCenterCommissionPayment
    {
        $commission = $this->syncForVendor($vendor);

        if (! $commission->isAwaitingPayment()) {
            throw new RuntimeException('Ödeme için temsilci onayı bekleniyor.');
        }

        $amount = (float) $commission->approved_amount;
        if ($amount <= 0) {
            throw new RuntimeException('Geçersiz ödeme tutarı.');
        }

        return DB::transaction(function () use ($commission, $vendor, $admin, $amount, $note) {
            $payment = CallCenterCommissionPayment::query()->create([
                'vendor_id' => $vendor->id,
                'admin_id' => $commission->admin_id,
                'amount' => $amount,
                'paid_by_admin_id' => $admin->id,
                'note' => $note,
            ]);

            $commission->paid_total = (float) $commission->paid_total + $amount;
            $commission->approved_amount = null;
            $commission->agent_approved_at = null;
            $commission->status = CallCenterCommission::STATUS_OPEN;
            $commission->save();

            // Güncel hesaplamayı yenile
            $this->syncForVendor($vendor->fresh());

            return $payment;
        });
    }

    /**
     * @return array{pending: float, awaiting_payment: float, paid_total: float, calculated_total: float}
     */
    public function agentTotals(int $adminId): array
    {
        $rows = CallCenterCommission::query()->where('admin_id', $adminId)->get();

        $pending = 0.0;
        $awaiting = 0.0;
        $paid = 0.0;
        $calculated = 0.0;

        foreach ($rows as $row) {
            $calculated += (float) $row->calculated_total;
            $paid += (float) $row->paid_total;
            if ($row->isAwaitingPayment()) {
                $awaiting += (float) $row->approved_amount;
            } else {
                $pending += $row->pendingApprovalAmount();
            }
        }

        return [
            'calculated_total' => $calculated,
            'pending' => $pending,
            'awaiting_payment' => $awaiting,
            'paid_total' => $paid,
        ];
    }

    /**
     * @return Collection<int, CallCenterCommissionPayment>
     */
    public function recentPayments(?int $adminId = null, int $limit = 20): Collection
    {
        $query = CallCenterCommissionPayment::query()
            ->with(['vendor', 'agent', 'paidBy'])
            ->latest('id');

        if ($adminId !== null) {
            $query->where('admin_id', $adminId);
        }

        return $query->limit($limit)->get();
    }
}
