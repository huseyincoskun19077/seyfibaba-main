@extends('seller.master_layout')
@section('title')
<title>Eklentiler</title>
@endsection

@section('seller-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Eklentiler</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">Panel</a></div>
                <div class="breadcrumb-item">Eklentiler</div>
            </div>
        </div>

        <div class="section-body">
            <div class="alert alert-light border">
                Entegrasyonlar isteğe bağlıdır. Bağlamadığınız eklentiler Seyfibaba ürün, sipariş ve ödeme akışınızı etkilemez.
                İlk olarak <strong>Sentos</strong> ile başlayabilirsiniz.
            </div>

            <div class="row">
                @foreach($integrations as $item)
                    <div class="col-md-6 col-xl-4 mb-4">
                        <div class="card h-100">
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
                                @if($item['enabled_globally'] && $item['route'])
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
