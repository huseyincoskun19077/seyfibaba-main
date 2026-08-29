@extends('admin.master_layout')
@section('title')
<title>İkinci El İlanlar</title>
@endsection

@section('admin-content')
@php
  $statusLabels = [
    'draft' => 'Taslak',
    'pending' => 'Onay Bekliyor',
    'active' => 'Yayında',
    'inactive' => 'Pasif',
    'rejected' => 'Reddedildi',
    'sold' => 'Satıldı',
  ];
  $statusBadge = [
    'draft' => 'badge-secondary',
    'pending' => 'badge-info',
    'active' => 'badge-success',
    'inactive' => 'badge-warning',
    'rejected' => 'badge-danger',
    'sold' => 'badge-dark',
  ];

  $conditionBadge = [
    'new' => 'badge-success',
    'lightly_used' => 'badge-info',
    'used' => 'badge-primary',
    'defective' => 'badge-danger',
  ];
@endphp
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İkinci El İlanlar</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.second-hand.index') }}">İkinci El</a></div>
        <div class="breadcrumb-item">İlanlar</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card mb-3">
        <div class="card-body py-3">
          <form method="GET" action="{{ route('admin.second-hand.listings') }}">
            <div class="row">
              <div class="col-md-3">
                <div class="form-group mb-2">
                  <label>Durum</label>
                  <select class="form-control" name="status">
                    <option value="all">Tümü</option>
                    <option value="pending" {{ request('status')==='pending' ? 'selected' : '' }}>Onay Bekliyor</option>
                    <option value="active" {{ request('status')==='active' ? 'selected' : '' }}>Yayında</option>
                    <option value="inactive" {{ request('status')==='inactive' ? 'selected' : '' }}>Pasif</option>
                    <option value="draft" {{ request('status')==='draft' ? 'selected' : '' }}>Taslak</option>
                    <option value="rejected" {{ request('status')==='rejected' ? 'selected' : '' }}>Reddedildi</option>
                    <option value="sold" {{ request('status')==='sold' ? 'selected' : '' }}>Satıldı</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-2">
                  <label>Ürün Durumu</label>
                  <select class="form-control" name="condition">
                    <option value="all">Tümü</option>
                    @foreach($conditionOptions as $key => $label)
                      <option value="{{ $key }}" {{ request('condition')===$key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-2">
                  <label>Arama</label>
                  <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Başlık / açıklama">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-2">
                  <label>Kullanıcı E-posta</label>
                  <input class="form-control" name="user_email" value="{{ request('user_email') }}" placeholder="örn: salon@...">
                </div>
              </div>
            </div>
            <div class="d-flex" style="gap:8px;">
              <button class="btn btn-primary" type="submit"><i class="fas fa-filter mr-1"></i> Filtrele</button>
              <a class="btn btn-outline-secondary" href="{{ route('admin.second-hand.listings') }}">Sıfırla</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h4>İlanlar</h4></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>İlan</th>
                  <th>Kullanıcı</th>
                  <th>Fiyat</th>
                  <th>Lokasyon</th>
                  <th>Durum</th>
                  <th>Yayın</th>
                  <th>Görünt.</th>
                  <th>Mesaj</th>
                  <th>Son mesaj</th>
                  <th style="min-width:260px;">İşlem</th>
                </tr>
              </thead>
              <tbody>
                @forelse($listings as $l)
                  <tr>
                    <td>{{ $l->id }}</td>
                    <td>
                      <strong>{{ $l->title }}</strong><br>
                      <span class="text-muted small">
                        {{ optional($l->category)->name ? optional($l->category)->name : '-' }}
                      </span>
                      <div class="mt-1">
                        <span class="badge {{ $conditionBadge[$l->condition] ?? 'badge-secondary' }}">
                          {{ $conditionOptions[$l->condition] ?? $l->condition }}
                        </span>
                      </div>
                      @if($l->inactive_reason)
                        <div class="text-muted small mt-1">Pasif sebep: {{ $l->inactive_reason }}</div>
                      @endif
                    </td>
                    <td class="small">
                      <strong>{{ optional($l->user)->name ?? '-' }}</strong><br>
                      <span class="text-muted">{{ optional($l->user)->email ?? '-' }}</span>
                    </td>
                    <td><strong>{{ number_format((float)$l->price, 2, ',', '.') }}</strong></td>
                    <td class="small">
                      {{ optional($l->city)->name ?? '-' }}<br>
                      <span class="text-muted">{{ $l->district ?: '-' }}</span>
                    </td>
                    <td>
                      <span class="badge {{ $statusBadge[$l->status] ?? 'badge-secondary' }}">
                        {{ $statusLabels[$l->status] ?? $l->status }}
                      </span>
                    </td>
                    <td class="small">
                      {{ optional($l->published_at)->format('d.m.Y H:i') ?: '-' }}
                    </td>
                    <td>{{ (int) $l->views_count }}</td>
                    <td class="small">
                      <span class="badge badge-light">{{ (int) ($l->messages_count ?? 0) }}</span>
                      <span class="text-muted">/</span>
                      <span class="badge badge-light">{{ (int) ($l->conversations_count ?? 0) }}</span>
                      <span class="text-muted">·</span>
                      <span class="badge badge-light">{{ (int) ($l->unique_buyers_count ?? 0) }}</span>
                    </td>
                    <td class="small">
                      <div class="text-muted">
                        {{ $l->last_message_at ? \Carbon\Carbon::parse($l->last_message_at)->format('d.m.Y H:i') : '—' }}
                      </div>
                      @if(!empty($l->last_message_preview))
                        <div title="{{ $l->last_message_preview }}" class="text-muted small" style="max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                          {{ $l->last_message_preview }}
                        </div>
                      @endif
                    </td>
                    <td>
                      <div class="mb-2">
                        @if($l->is_featured)
                          <span class="badge badge-warning">Öne çıkan</span>
                        @endif
                        @if($l->is_urgent)
                          <span class="badge badge-danger">Acil</span>
                        @endif
                      </div>

                      <div class="d-flex flex-wrap" style="gap:6px;">
                        @if(!$l->is_featured)
                          <form method="POST" action="{{ route('admin.second-hand.listings.featured', $l->id) }}">
                            @csrf
                            @method('PUT')
                            <button class="btn btn-sm btn-outline-warning" type="submit">Öne çıkar</button>
                          </form>
                        @else
                          <form method="POST" action="{{ route('admin.second-hand.listings.featured.unset', $l->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-warning" type="submit">Öne çıkarma</button>
                          </form>
                        @endif

                        @if(!$l->is_urgent)
                          <form method="POST" action="{{ route('admin.second-hand.listings.urgent', $l->id) }}">
                            @csrf
                            @method('PUT')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Acil</button>
                          </form>
                        @else
                          <form method="POST" action="{{ route('admin.second-hand.listings.urgent.unset', $l->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">Acil kaldır</button>
                          </form>
                        @endif
                      </div>

                      @if($l->status === 'pending')
                        <form method="POST" action="{{ route('admin.second-hand.listings.approve', $l->id) }}" class="mb-2">
                          @csrf
                          @method('PUT')
                          <button class="btn btn-success btn-sm btn-block" type="submit">
                            <i class="fas fa-check mr-1"></i> Onayla (Yayınla)
                          </button>
                        </form>
                        <form method="POST" action="{{ route('admin.second-hand.listings.reject', $l->id) }}" class="mb-2">
                          @csrf
                          @method('PUT')
                          <div class="form-group mb-2">
                            <input class="form-control form-control-sm" name="review_note" placeholder="Red sebebi (zorunlu)">
                          </div>
                          <button class="btn btn-danger btn-sm btn-block" type="submit">
                            <i class="fas fa-times mr-1"></i> Reddet
                          </button>
                        </form>
                      @elseif($l->status === 'active')
                        <form method="POST" action="{{ route('admin.second-hand.listings.deactivate', $l->id) }}" class="mb-2">
                          @csrf
                          @method('PUT')
                          <div class="form-group mb-2">
                            <input class="form-control form-control-sm" name="inactive_reason" placeholder="Pasif sebep (opsiyonel)">
                          </div>
                          <button class="btn btn-warning btn-sm btn-block" type="submit">
                            <i class="fas fa-pause mr-1"></i> Pasife Al
                          </button>
                        </form>
                      @elseif($l->status === 'inactive')
                        <form method="POST" action="{{ route('admin.second-hand.listings.activate', $l->id) }}">
                          @csrf
                          @method('PUT')
                          <button class="btn btn-success btn-sm btn-block" type="submit">
                            <i class="fas fa-play mr-1"></i> Yayına Al
                          </button>
                        </form>
                      @else
                        <span class="text-muted small">—</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="11" class="text-center text-muted py-4">Kayıt bulunamadı.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            {{ $listings->links() }}
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

