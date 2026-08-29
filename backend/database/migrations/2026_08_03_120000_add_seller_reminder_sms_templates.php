<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sms_templates', 'slug')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->string('slug', 64)->nullable()->unique()->after('id');
                $table->string('category', 32)->nullable()->after('name');
            });
        }

        $templates = [
            [
                'slug' => 'seller_reminder_login',
                'name' => 'Satıcı — Giriş hatırlatması',
                'category' => 'seller_reminder',
                'subject' => 'Giriş yapılmadı / şifre hatırlatma',
                'description' => "Sayin {{contact_name}}, {{shop_name}} satici hesabiniz hazir.\nGiris: {{login_url}}\nKullanici adi: {{login_phone}}\nSifre: {{password}}\nSeyfibaba",
            ],
            [
                'slug' => 'seller_reminder_kyc',
                'name' => 'Satıcı — KYC hatırlatması',
                'category' => 'seller_reminder',
                'subject' => 'Vergi levhası / KYC hatırlatma',
                'description' => "Sayin {{contact_name}}, {{shop_name}} magazasi icin vergi levhanizi (KYC) yuklemeniz gerekiyor.\nPanel: {{login_url}}\nSeyfibaba",
            ],
            [
                'slug' => 'seller_reminder_product',
                'name' => 'Satıcı — Ürün hatırlatması',
                'category' => 'seller_reminder',
                'subject' => 'Ürün yükleme hatırlatma',
                'description' => "Sayin {{contact_name}}, {{shop_name}} magazasiniz hazir. Urun ekleyerek satisa baslayabilirsiniz.\nPanel: {{login_url}}\nSeyfibaba",
            ],
        ];

        foreach ($templates as $template) {
            $exists = DB::table('sms_templates')->where('slug', $template['slug'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('sms_templates')->insert([
                'slug' => $template['slug'],
                'name' => $template['name'],
                'category' => $template['category'],
                'subject' => $template['subject'],
                'description' => $template['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('sms_templates')
            ->whereIn('slug', [
                'seller_reminder_login',
                'seller_reminder_kyc',
                'seller_reminder_product',
            ])
            ->delete();

        if (Schema::hasColumn('sms_templates', 'slug')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->dropColumn(['slug', 'category']);
            });
        }
    }
};
