<?php

use App\Models\AiChatKnowledge;
use App\Models\ContactPage;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const WHATSAPP_E164 = '908503035073';
    private const WHATSAPP_DISPLAY = '0850 303 5073';
    private const PHONE_DISPLAY = '0850 303 5073';

    public function up(): void
    {
        if (Schema::hasTable('contact_pages') && Schema::hasColumn('contact_pages', 'whatsapp')) {
            $contact = ContactPage::query()->first();
            if ($contact) {
                $contact->whatsapp = self::WHATSAPP_E164;
                // Resmi hat: WhatsApp ile aynı (eski kişisel numara kalmasın)
                if (! empty($contact->phone) && preg_match('/543\s*501\s*19\s*95/', (string) $contact->phone)) {
                    $contact->phone = self::PHONE_DISPLAY;
                }
                if (empty($contact->phone)) {
                    $contact->phone = self::PHONE_DISPLAY;
                }
                $contact->save();
            }
        }

        $replacements = [
            'https://wa.me/905435011995' => 'https://wa.me/'.self::WHATSAPP_E164,
            'wa.me/905435011995' => 'wa.me/'.self::WHATSAPP_E164,
            '0 (543) 501 19 95' => self::PHONE_DISPLAY,
            '0543 501 19 95' => self::WHATSAPP_DISPLAY,
            '05435011995' => '08503035073',
        ];

        if (Schema::hasTable('ai_chat_knowledge')) {
            foreach (AiChatKnowledge::query()->get() as $row) {
                $answer = (string) $row->answer;
                $updated = str_replace(array_keys($replacements), array_values($replacements), $answer);
                // WhatsApp satırını netleştir
                if (str_contains($updated, 'WhatsApp') && ! str_contains($updated, self::WHATSAPP_E164)) {
                    $updated = preg_replace(
                        '/WhatsApp:[^\n]*/u',
                        'WhatsApp: '.self::WHATSAPP_DISPLAY.' (https://wa.me/'.self::WHATSAPP_E164.')',
                        $updated
                    ) ?? $updated;
                }
                if ($updated !== $answer) {
                    $row->answer = $updated;
                    $row->save();
                }
            }
        }

        if (Schema::hasTable('settings')) {
            $setting = Setting::query()->first();
            if ($setting && ! empty($setting->ai_chat_system_prompt)) {
                $prompt = str_replace(array_keys($replacements), array_values($replacements), (string) $setting->ai_chat_system_prompt);
                $setting->ai_chat_system_prompt = $prompt;
                $setting->save();
            }
        }

        if (Schema::hasTable('footers') && Schema::hasColumn('footers', 'phone')) {
            \Illuminate\Support\Facades\DB::table('footers')
                ->where('phone', 'like', '%543%501%')
                ->orWhere('phone', 'like', '%5435011995%')
                ->update(['phone' => self::PHONE_DISPLAY]);
        }
    }

    public function down(): void
    {
        // no-op
    }
};
