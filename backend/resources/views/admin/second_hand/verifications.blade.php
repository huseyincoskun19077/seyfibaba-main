@extends('admin.master_layout')
@section('title')
<title>İkinci El Doğrulamalar</title>
@endsection

@section('admin-content')
@php
  $statusLabels = [
    'pending' => 'Onay Bekliyor',
    'approved' => 'Onaylı',
    'rejected' => 'Reddedildi',
  ];
  $statusBadge = [
    'pending' => 'badge-warning',
    'approved' => 'badge-success',
    'rejected' => 'badge-danger',
  ];
@endphp
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İkinci El Doğrulamalar</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.second-hand.index') }}">İkinci El</a></div>
        <div class="breadcrumb-item">Doğrulamalar</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header"><h4>Doğrulama Kuyruğu</h4></div>
        <div class="card-body">
          <div class="d-flex flex-wrap align-items-center mb-3" style="gap:8px;">
            <a href="{{ route('admin.second-hand.verifications') }}" class="btn btn-sm {{ !request('status') || request('status') == 'all' ? 'btn-primary' : 'btn-outline-primary' }}">Tümü</a>
            <a href="{{ route('admin.second-hand.verifications', ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') == 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">Bekleyen</a>
            <a href="{{ route('admin.second-hand.verifications', ['status' => 'approved']) }}" class="btn btn-sm {{ request('status') == 'approved' ? 'btn-success' : 'btn-outline-success' }}">Onaylı</a>
            <a href="{{ route('admin.second-hand.verifications', ['status' => 'rejected']) }}" class="btn btn-sm {{ request('status') == 'rejected' ? 'btn-danger' : 'btn-outline-danger' }}">Reddedilen</a>
          </div>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Kullanıcı</th>
                  <th>İşletme</th>
                  <th>Vergi No</th>
                  <th>Sicil</th>
                  <th>Belge</th>
                  <th>Durum</th>
                  <th style="min-width:320px;">İşlem</th>
                </tr>
              </thead>
              <tbody>
                @forelse($verifications as $v)
                  <tr>
                    <td>{{ $v->id }}</td>
                    <td class="small">
                      <strong>{{ optional($v->user)->name ?? '-' }}</strong><br>
                      <span class="text-muted">{{ optional($v->user)->email ?? '-' }}</span>
                    </td>
                    <td>{{ $v->business_name }}</td>
                    <td><code>{{ $v->tax_number }}</code></td>
                    <td class="small">
                      @if($v->barber_registry_number)
                        <code>{{ $v->barber_registry_number }}</code>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td>
                      @if($v->tax_document_path)
                        <a class="btn btn-info btn-sm" href="{{ route('admin.second-hand.verifications.download-tax-document', $v->id) }}">
                          <i class="fas fa-download mr-1"></i> İndir
                        </a>
                        @if($v->barber_document_path)
                          <a class="btn btn-outline-info btn-sm ml-1" href="{{ route('admin.second-hand.verifications.download-barber-document', $v->id) }}">
                            <i class="fas fa-download mr-1"></i> Sicil
                          </a>
                        @endif
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td>
                      <span class="badge {{ $statusBadge[$v->status] ?? 'badge-secondary' }}">
                        {{ $statusLabels[$v->status] ?? $v->status }}
                      </span>
                    </td>
                    <td>
                      <div class="row">
                        <div class="col-md-6">
                          <form method="POST" action="{{ route('admin.second-hand.verifications.approve', $v->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="form-group mb-2">
                              <textarea name="admin_note" class="form-control" rows="2" placeholder="Onay notu (opsiyonel)">{{ $v->admin_note }}</textarea>
                            </div>
                            <button class="btn btn-success btn-block btn-sm" type="submit" {{ $v->status === 'approved' ? 'disabled' : '' }}>
                              <i class="fas fa-check mr-1"></i> Onayla
                            </button>
                          </form>
                        </div>
                        <div class="col-md-6">
                          <form method="POST" action="{{ route('admin.second-hand.verifications.reject', $v->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="form-group mb-2">
                              <textarea name="admin_note" class="form-control" rows="2" placeholder="Red nedeni (zorunlu)" {{ $v->status === 'approved' ? 'disabled' : '' }} required>{{ $v->status === 'rejected' ? $v->admin_note : '' }}</textarea>
                            </div>
                            <button class="btn btn-danger btn-block btn-sm" type="submit" {{ $v->status === 'approved' ? 'disabled' : '' }}>
                              <i class="fas fa-times mr-1"></i> Reddet
                            </button>
                          </form>
                        </div>
                      </div>
                      <div class="mt-2 text-muted small">
                        İncelenme: {{ optional($v->reviewed_at)->format('d.m.Y H:i') ?: 'Henüz incelenmedi' }}
                        @if($v->reviewer)
                          | İnceleyen: {{ $v->reviewer->name }}
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">Henüz doğrulama kaydı yok.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            {{ $verifications->links() }}
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

