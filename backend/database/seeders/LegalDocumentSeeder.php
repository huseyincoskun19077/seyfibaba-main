<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use App\Support\LegalDocumentMarkdownConverter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = database_path('seeders/sources/legal');

        $documents = [
            [
                'slug' => 'terms',
                'title' => 'Şartlar ve Koşullar',
                'sort_order' => 10,
                'is_published' => true,
                'requires_consent' => true,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'Şartlar ve Koşullar | Seyfibaba',
                'meta_description' => 'Seyfibaba pazaryeri kullanım şartları ve koşulları.',
                'source' => 'terms.md',
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Gizlilik Politikası',
                'sort_order' => 20,
                'is_published' => true,
                'requires_consent' => false,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'Gizlilik Politikası | Seyfibaba',
                'meta_description' => 'Seyfibaba gizlilik politikası ve kişisel veri koruma uygulamaları.',
                'source' => 'privacy-policy.md',
            ],
            [
                'slug' => 'privacy-agreement',
                'title' => 'Gizlilik Sözleşmesi',
                'sort_order' => 30,
                'is_published' => true,
                'requires_consent' => false,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'Gizlilik Sözleşmesi | Seyfibaba',
                'meta_description' => 'Seyfibaba gizlilik sözleşmesi metni.',
                'source' => 'privacy-agreement.md',
            ],
            [
                'slug' => 'kvkk-aydinlatma',
                'title' => 'KVKK Aydınlatma Metni',
                'sort_order' => 40,
                'is_published' => true,
                'requires_consent' => true,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'KVKK Aydınlatma Metni | Seyfibaba',
                'meta_description' => '6698 sayılı KVKK kapsamında aydınlatma metni.',
                'source' => null,
                'placeholder' => 'Bu metin henüz yüklenmedi. Lütfen KVKK Aydınlatma Metni dosyasını admin panelinden ekleyin.',
            ],
            [
                'slug' => 'kvkk-acik-riza',
                'title' => 'KVKK Açık Rıza Metni',
                'sort_order' => 50,
                'is_published' => true,
                'requires_consent' => false,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'KVKK Açık Rıza Metni | Seyfibaba',
                'meta_description' => 'Ticari ileti ve pazarlama faaliyetleri için açık rıza metni.',
                'source' => 'kvkk-acik-riza.md',
            ],
            [
                'slug' => 'kvkk-basvuru',
                'title' => 'KVKK Başvuru Formu',
                'sort_order' => 60,
                'is_published' => true,
                'requires_consent' => false,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'KVKK Başvuru Formu | Seyfibaba',
                'meta_description' => 'Veri sahibi başvuru formu ve başvuru yöntemleri.',
                'source' => 'kvkk-basvuru.md',
            ],
            [
                'slug' => 'distance-sales',
                'title' => 'Mesafeli Satış Sözleşmesi',
                'sort_order' => 70,
                'is_published' => true,
                'requires_consent' => true,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'Mesafeli Satış Sözleşmesi | Seyfibaba',
                'meta_description' => '6502 sayılı Kanun kapsamında mesafeli satış sözleşmesi.',
                'source' => 'distance-sales.md',
            ],
            [
                'slug' => 'pre-information',
                'title' => 'Ön Bilgilendirme Formu',
                'sort_order' => 80,
                'is_published' => true,
                'requires_consent' => true,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'Ön Bilgilendirme Formu | Seyfibaba',
                'meta_description' => 'Mesafeli satış ön bilgilendirme formu.',
                'source' => 'pre-information.md',
            ],
            [
                'slug' => 'delivery-return',
                'title' => 'Teslimat ve İade Şartları',
                'sort_order' => 90,
                'is_published' => true,
                'requires_consent' => false,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'Teslimat ve İade Şartları | Seyfibaba',
                'meta_description' => 'Teslimat süreleri, kargo ve iade koşulları.',
                'source' => 'delivery-return.md',
            ],
            [
                'slug' => 'seller-terms',
                'title' => 'Satıcı Şartları ve Koşulları',
                'sort_order' => 100,
                'is_published' => true,
                'requires_consent' => true,
                'is_active' => true,
                'category' => 'seller',
                'meta_title' => 'Satıcı Şartları | Seyfibaba',
                'meta_description' => 'Seyfibaba satıcı paneli kullanım şartları.',
                'source' => 'seller-terms.md',
            ],
            [
                'slug' => 'prohibited-products',
                'title' => 'Yasaklı Ürünler Politikası',
                'sort_order' => 110,
                'is_published' => true,
                'requires_consent' => false,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'Yasaklı Ürünler Politikası | Seyfibaba',
                'meta_description' => 'Platformda satışı yasak ürün ve hizmetler.',
                'source' => 'prohibited-products.md',
            ],
            [
                'slug' => 'second-hand-rules',
                'title' => 'İkinci El İlan Kuralları',
                'sort_order' => 120,
                'is_published' => true,
                'requires_consent' => true,
                'is_active' => true,
                'category' => 'legal',
                'meta_title' => 'İkinci El İlan Kuralları | Seyfibaba',
                'meta_description' => 'İkinci el ilan yayınlama kuralları ve yükümlülükler.',
                'source' => 'second-hand-rules.md',
            ],
            [
                'slug' => 'commission-policy',
                'title' => 'Komisyon Politikası',
                'sort_order' => 130,
                'is_published' => true,
                'requires_consent' => false,
                'is_active' => true,
                'category' => 'seller',
                'meta_title' => 'Komisyon Politikası | Seyfibaba',
                'meta_description' => 'Satıcı komisyon oranları ve uygulama esasları.',
                'source' => null,
                'placeholder' => 'Bu metin henüz yüklenmedi. Lütfen Komisyon Politikası metnini admin panelinden ekleyin.',
            ],
            [
                'slug' => 'payout-info',
                'title' => 'Hakediş Bilgileri',
                'sort_order' => 140,
                'is_published' => true,
                'requires_consent' => false,
                'is_active' => true,
                'category' => 'seller',
                'meta_title' => 'Hakediş Bilgileri | Seyfibaba',
                'meta_description' => 'Satıcı hakediş ödeme süreçleri ve bilgileri.',
                'source' => null,
                'placeholder' => 'Bu metin henüz yüklenmedi. Lütfen Hakediş Bilgileri metnini admin panelinden ekleyin.',
            ],
        ];

        foreach ($documents as $doc) {
            $content = $this->resolveContent($sourceDir, $doc);

            LegalDocument::updateOrCreate(
                ['slug' => $doc['slug']],
                [
                    'title' => $doc['title'],
                    'content' => $content,
                    'version' => '1.0',
                    'meta_title' => $doc['meta_title'],
                    'meta_description' => $doc['meta_description'],
                    'is_published' => $doc['is_published'],
                    'requires_consent' => $doc['requires_consent'],
                    'is_active' => $doc['is_active'],
                    'sort_order' => $doc['sort_order'],
                    'category' => $doc['category'],
                ]
            );
        }
    }

    private function resolveContent(string $sourceDir, array $doc): string
    {
        if (!empty($doc['source'])) {
            $path = $sourceDir.DIRECTORY_SEPARATOR.$doc['source'];
            if (File::exists($path)) {
                return LegalDocumentMarkdownConverter::toHtml(File::get($path));
            }
        }

        $placeholder = $doc['placeholder'] ?? 'İçerik henüz eklenmedi.';

        return '<p><strong>'.htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8').'</strong></p>';
    }
}
