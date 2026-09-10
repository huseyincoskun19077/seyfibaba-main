@extends('seller.master_layout')
@section('title')
<title>Entegrasyon</title>
@endsection

@section('seller-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Entegrasyon</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">Panel</a></div>
                <div class="breadcrumb-item">Entegrasyon</div>
            </div>
        </div>

        <div class="section-body">
            <div class="alert alert-light border">
                Aynı anda <strong>yalnızca bir katalog entegrasyonu</strong> açılabilir (ör. Sentos veya Softtr).
                İkisi birden stok/sipariş karışıklığı yaratır. Aktif olanı kapatmadan diğerini açamazsınız.
                @if(!empty($activeIntegration))
                    <div class="mt-2 mb-0">
                        Şu an aktif: <span class="badge badge-success">{{ $activeIntegrationLabel }}</span>
                    </div>
                @endif
            </div>

            <div class="row">
                @foreach($integrations as $item)
                    <div class="col-md-6 col-xl-4 mb-4">
                        <div class="card h-100 {{ !empty($item['locked']) ? 'border-secondary' : '' }}">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <span class="btn btn-icon btn-primary mr-3" style="pointer-events:none;">
                                            <i class="{{ $item['icon'] }}"></i>
                                        </span>
                                        <div>
                                            <h5 class="mb-1">{{ $item['name'] }}</h5>
                                            <span class="badge badge-{{ $item['enabled_globally'] ? 'info' : 'secondary' }}">{{ $item['badge'] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted flex-grow-1">{{ $item['description'] }}</p>
                                <p class="mb-3">
                                    <strong>Durum:</strong>
                                    {{ $item['status_label'] }}
                                    @if($item['connected'])
                                        <span class="badge badge-success ml-1">Aktif</span>
                                    @endif
                                </p>
                                @if(!empty($item['locked']))
                                    <div class="alert alert-warning py-2 small mb-2">
                                        {{ $item['lock_message'] ?: 'Başka bir entegrasyon açıkken kullanılamaz.' }}
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-block" disabled>Kilitli</button>
                                @elseif($item['enabled_globally'] && $item['route'])
                                    <a href="{{ route($item['route']) }}" class="btn btn-primary btn-block">
                                        {{ $item['connected'] ? 'Ayarları Aç' : 'Kur / Bağla' }}
                                    </a>
                                @else
                                    <button type="button" class="btn btn-secondary btn-block" disabled>Yakında</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection
