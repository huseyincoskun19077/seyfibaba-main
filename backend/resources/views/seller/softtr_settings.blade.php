@extends('seller.master_layout')
@section('title')
<title>Softtr Entegrasyonu</title>
@endsection

@section('seller-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Softtr Entegrasyonu</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">Panel</a></div>
                <div class="breadcrumb-item"><a href="{{ route('seller.integrations.index') }}">Entegrasyon</a></div>
                <div class="breadcrumb-item">Softtr</div>
            </div>
        </div>

        <div class="section-body">
            @if(!empty($integrationBlocked))
                <div class="alert alert-warning">{{ $blockMessage }}</div>
            @endif

            <div class="alert alert-info">
                <strong>Softtr (yalnızca ürün):</strong>
                Softtr API ürün listeleme + stok/fiyat okuma destekler; <strong>Seyfibaba siparişlerini Softtr’ye gönderme yok</strong>
                (dokümandaki sipariş endpoint’leri Softtr siparişlerini okumak/güncellemek içindir).
                Ürünler Softtr → Seyfibaba çekilir. Softtr’daki <code>updateStokAndPrice</code> Softtr’ye yazar; biz kullanmayız.
                Saatlik senkron: yeni ürün + fiyat + stok. Sentos ile aynı anda açılamaz.
                Doküman: <a href="https://api.softtr.net/docbeta/" target="_blank" rel="noopener">api.softtr.net/docbeta</a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-plug mr-2"></i>API Bilgileri</h4></div>
                        <div class="card-body">
                            <form action="{{ route('seller.softtr.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="form-group">
                                    <label for="api_base_url">Softtr mağaza adresi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('api_base_url') is-invalid @enderror"
                                           id="api_base_url" name="api_base_url"
                                           value="{{ old('api_base_url', $settings->api_base_url ?? '') }}"
                                           placeholder="magazaadi.com veya https://www.magazaadi.com" required>
                                    <small class="text-muted">
                                        Örnek: <code>magazaadi.com</code> → <code>https://www.magazaadi.com/api</code>
                                        @if(!empty($normalizedPreview))
                                            — kayıtlı: <code>{{ $normalizedPreview }}</code>
                                        @endif
                                    </small>
                                    @error('api_base_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="form-group">
                                    <label for="api_user">API Kullanıcı <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="api_user" name="api_user" value=""
                                           autocomplete="new-password"
                                           placeholder="{{ $settings ? 'Değiştirmek için yeni değer yazın' : 'Softtr API kullanıcı' }}"
                                           {{ $settings ? '' : 'required' }}>
                                </div>

                                <div class="form-group">
                                    <label for="api_password">API Şifre <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="api_password" name="api_password" value=""
                                           autocomplete="new-password"
                                           placeholder="{{ $settings ? 'Değiştirmek için yeni değer yazın' : 'Softtr API şifre' }}"
                                           {{ $settings ? '' : 'required' }}>
                                    <small class="text-muted">Softtr panel → Yardım / Destek üzerinden API kullanıcı-şifre talep edilir (HTTP Basic Auth).</small>
                                </div>

                                <div class="form-group">
                                    <label class="custom-switch mt-2">
                                        <input type="checkbox" name="is_enabled" value="1" class="custom-switch-input"
                                               {{ old('is_enabled', $settings->is_enabled ?? false) ? 'checked' : '' }}
                                               {{ !empty($integrationBlocked) && !($settings->is_enabled ?? false) ? 'disabled' : '' }}>
                                        <span class="custom-switch-indicator"></span>
                                        <span class="custom-switch-description">Softtr entegrasyonunu bu mağaza için aç</span>
                                    </label>
                                    <p class="text-muted mb-0 mt-2">
                                        Otomatik senkron: <strong>saatte 1 kez</strong> (cron).
                                        Her turda Softtr’ye <strong>sayfa sayfa</strong> istek atılır — sayfa başına en fazla <strong>100 ürün</strong>, sayfalar arası ~2 sn beklenir.
                                        Elle «Ürünleri Çek» de aynı sayfalama ile çalışır.
                                    </p>
                                </div>

                                <button type="submit" class="btn btn-primary" {{ !empty($integrationBlocked) && !($settings->is_enabled ?? false) ? 'disabled' : '' }}>Kaydet</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><h4>Durum</h4></div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Entegrasyon:</strong>
                                @if($settings?->is_enabled)
                                    <span class="badge badge-success">Açık</span>
                                @else
                                    <span class="badge badge-secondary">Kapalı</span>
                                @endif
                            </p>
                            <p class="mb-2">
                                <strong>Son test:</strong>
                                @if($settings?->last_tested_at)
                                    {{ $settings->last_tested_at->format('d.m.Y H:i') }} —
                                    @if($settings->last_test_status === 'success')
                                        <span class="text-success">Başarılı</span>
                                    @else
                                        <span class="text-danger">Başarısız</span>
                                    @endif
                                @else
                                    Henüz test edilmedi
                                @endif
                            </p>
                            @if($settings?->last_test_message)
                                <p class="small text-muted mb-3">{{ $settings->last_test_message }}</p>
                            @endif
                            <hr>
                            <p class="mb-2">
                                <strong>Son ürün senkronu:</strong>
                                @if($settings?->last_sync_at)
                                    {{ $settings->last_sync_at->format('d.m.Y H:i') }}
                                    — {{ $settings->last_sync_status === 'success' ? 'Başarılı' : 'Başarısız / kısmi' }}
                                @else
                                    Henüz çalıştırılmadı
                                @endif
                            </p>
                            @if($settings?->last_sync_message)
                                <p class="small text-muted mb-3">{{ $settings->last_sync_message }}</p>
                            @endif

                            @if($settings)
                                <form action="{{ route('seller.softtr.test') }}" method="POST" class="mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-vial mr-1"></i> Bağlantıyı Test Et</button>
                                </form>
                                <form action="{{ route('seller.softtr.sync-products') }}" method="POST" class="mb-2"
                                      onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='Çekiliyor…'; return true;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-block" {{ ! $settings->is_enabled ? 'disabled' : '' }}>
                                        <i class="fas fa-sync mr-1"></i> Ürünleri Çek / Güncelle
                                    </button>
                                </form>
                                <form action="{{ route('seller.softtr.disable') }}" method="POST"
                                      onsubmit="return confirm('Softtr bu mağaza için kapatılsın mı? Ürünleriniz silinmez.');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-block">Entegrasyonu Kapat</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h4><i class="fas fa-boxes mr-2"></i>Softtr’dan gelen ürünler</h4></div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                Softtr her varyantı ayrı satır gönderebilir; aynı SKU tek ürüne birleştirilir.
                                Kategori eşlemesi Seyfibaba tarafındadır. Detay düzenleme Ürünler menüsünden; Softtr’a yazılmaz.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-striped table-md">
                                    <thead>
                                    <tr>
                                        <th style="width:70px;">Resim</th>
                                        <th>Ürün</th>
                                        <th>Kategori</th>
                                        <th>Alt kategori</th>
                                        <th>Alt alt kategori</th>
                                        <th>SKU</th>
                                        <th>Fiyat</th>
                                        <th>Stok</th>
                                        <th>Son senkron</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse(($mappedProducts ?? []) as $map)
                                        @php $p = $map->product; @endphp
                                        <tr>
                                            <td>
                                                @if($p)
                                                    <img src="{{ product_image_url($p->thumb_image) }}" alt=""
                                                         class="rounded" width="56" height="56" style="object-fit:cover;">
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $p?->short_name ?: ($p?->name ?: '—') }}</td>
                                            <td>{{ $p?->category?->name ?: '—' }}</td>
                                            <td>{{ $p?->subCategory?->name ?: '—' }}</td>
                                            <td>{{ $p?->childCategory?->name ?: '—' }}</td>
                                            <td><code>{{ $p?->sku ?: ($map->softtr_sku ?: '—') }}</code></td>
                                            <td>{{ $p ? number_format((float)$p->price, 2, ',', '.') : '—' }}</td>
                                            <td>
                                                @if($p)
                                                    @if((int)$p->qty < 1)
                                                        <span class="badge badge-warning">{{ (int)$p->qty }}</span>
                                                    @else
                                                        {{ (int)$p->qty }}
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $map->last_synced_at?->format('d.m.Y H:i') ?: '—' }}</td>
                                            <td>
                                                @if($p)
                                                    <a href="{{ route('seller.product.edit', $p->id) }}" class="btn btn-sm btn-primary">Ürünlerde aç</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10" class="text-center text-muted py-4">Henüz eşleşmiş ürün yok.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if(isset($mappedProducts) && method_exists($mappedProducts, 'links'))
                                <div class="mt-2">{{ $mappedProducts->links() }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
