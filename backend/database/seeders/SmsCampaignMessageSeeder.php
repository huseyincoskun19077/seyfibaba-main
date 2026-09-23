<?php

namespace Database\Seeders;

use App\Models\SmsCampaignMessage;
use Illuminate\Database\Seeder;

class SmsCampaignMessageSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            [
                'title' => 'Berberlere Özel Pazaryeri Tanıtımı',
                'message' => 'Kuaför Tedarik: Berberlere ozel pazaryeri! Toptan ve perakende urunlerinizi binlerce berbere ulastirin. Hemen ucretsiz kayit olun: kuafortedarik.com',
            ],
            [
                'title' => 'Toptan Satış Fırsatı',
                'message' => 'Kuaför Tedarik ile toptan satis firsati! Berber malzemelerinizi toptan fiyatlarla satin. Urunlerinizi yukleyin, tum Turkiye\'deki berberlere ulastirin. kuafortedarik.com',
            ],
            [
                'title' => 'Düşük Komisyon Avantajı',
                'message' => 'Kuaför Tedarik\'da sadece %10 komisyon orani ile urunlerinizi satin! Diger pazaryerlerine kiyasla cok daha avantajli. Hemen basvurun: kuafortedarik.com',
            ],
            [
                'title' => 'Ücretsiz Kayıt ve Ürün Yükleme',
                'message' => 'Kuaför Tedarik\'ya ucretsiz kayit olun! Mobilden veya webden kolayca urun yukleyin. Binlerce berbere ulasin. Kayit icin: kuafortedarik.com',
            ],
        ];

        foreach ($messages as $msg) {
            SmsCampaignMessage::firstOrCreate(
                ['title' => $msg['title']],
                [
                    'message' => $msg['message'],
                    'char_count' => mb_strlen($msg['message']),
                    'is_active' => true,
                ]
            );
        }
    }
}
