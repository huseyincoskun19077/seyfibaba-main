<?php

namespace App\Console\Commands;

use App\Models\SalonCrmAppointment;
use App\Services\FcmPushService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendSalonCrmAppointmentReminders extends Command
{
    protected $signature = 'salon-crm:appointment-remind';

    protected $description = 'Randevuya ~30 dk kala CRM push hatırlatması gönderir';

    public function handle(FcmPushService $fcm): int
    {
        $now = Carbon::now('Europe/Istanbul');
        $from = $now->copy()->addMinutes(25);
        $to = $now->copy()->addMinutes(35);
        $sent = 0;
        $processed = 0;

        SalonCrmAppointment::query()
            ->with(['salon', 'staff', 'customer'])
            ->whereIn('status', ['scheduled', 'pending'])
            ->where('is_block', false)
            ->whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->orderBy('id')
            ->chunkById(100, function ($appointments) use ($fcm, &$sent, &$processed) {
                foreach ($appointments as $appointment) {
                    $processed++;
                    $tokens = $this->tokensFor($appointment);
                    $title = 'Randevu hatırlatması';
                    $time = optional($appointment->starts_at)->format('H:i') ?? '';
                    $who = trim((string) ($appointment->customer_name ?: ''));
                    $service = $appointment->service_name ?: 'Randevu';
                    $body = $time !== ''
                        ? ($who !== ''
                            ? "{$who} · {$service} · saat {$time} (yaklaşık 30 dk)."
                            : "{$service} için {$time}'de randevu var (yaklaşık 30 dk).")
                        : "{$service} için yakında randevu var.";

                    $data = [
                        'type' => 'salon_crm_reminder',
                        'appointment_id' => (string) $appointment->id,
                        'salon_id' => (string) $appointment->salon_id,
                    ];

                    foreach ($tokens as $token) {
                        if ($fcm->sendToToken($token, $title, $body, $data)) {
                            $sent++;
                        }
                    }

                    $appointment->forceFill(['reminder_sent_at' => now()])->saveQuietly();
                }
            });

        $this->info("CRM randevu hatırlatması: işlenen={$processed}, gönderilen={$sent}");

        return self::SUCCESS;
    }

    /**
     * Müşteri + personel + patron (token varsa hepsine).
     *
     * @return list<string>
     */
    private function tokensFor(SalonCrmAppointment $appointment): array
    {
        $tokens = [];

        foreach ([
            $appointment->customer?->fcm_token,
            $appointment->staff?->fcm_token,
            $appointment->salon?->fcm_token,
        ] as $token) {
            if (is_string($token) && $token !== '') {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }
}
