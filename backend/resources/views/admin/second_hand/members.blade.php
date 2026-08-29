@extends('admin.master_layout')
@section('title')
<title>İkinci El Üyeler</title>
@endsection

@section('admin-content')
@php
  $statusLabels = [
    'pending' => 'Beklemede',
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
      <h1>İkinci El Üyeler</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.second-hand.index') }}">İkinci El</a></div>
        <div class="breadcrumb-item">Üyeler</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card mb-3">
        <div class="card-body py-3">
          <form method="GET" action="{{ route('admin.second-hand.members') }}">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group mb-2">
                  <label>Doğrulama durumu</label>
                  <select class="form-control" name="status">
                    <option value="all">Tümü</option>
                    <option value="pending" {{ request('status')==='pending' ? 'selected' : '' }}>Beklemede</option>
                    <option value="approved" {{ request('status')==='approved' ? 'selected' : '' }}>Onaylı</option>
                    <option value="rejected" {{ request('status')==='rejected' ? 'selected' : '' }}>Reddedildi</option>
                  </select>
                </div>
              </div>
              <div class="col-md-5">
                <div class="form-group mb-2">
                  <label>Arama</label>
                  <input class="form-control" name="q" value="{{ request('q') }}" placeholder="İsim, e-posta, işletme, vergi no">
                </div>
              </div>
            </div>
            <div class="d-flex" style="gap:8px;">
              <button class="btn btn-primary" type="submit"><i class="fas fa-filter mr-1"></i> Filtrele</button>
              <a class="btn btn-outline-secondary" href="{{ route('admin.second-hand.members') }}">Sıfırla</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="mb-0">Doğrulama kayıtları</h4>
          <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.second-hand.verifications') }}">Doğrulama kuyruğu</a>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Kullanıcı</th>
                  <th>İşletme / Vergi</th>
                  <th>Durum</th>
                  <th>İlanlar</th>
                  <th>Başvuru</th>
                  <th>İnceleyen</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $row)
                  <tr>
                    <td>{{ $row->id }}</td>
                    <td>
                      @if($row->user)
                        <strong>{{ $row->user->name ?? '—' }}</strong><br>
                        <span class="text-muted small">{{ $row->user->email ?? '—' }}</span>
                      @else
                        <span class="text-muted">Kullanıcı silinmiş</span>
                      @endif
                    </td>
                    <td>
                      <strong>{{ $row->business_name }}</strong><br>
                      <span class="text-muted small">{{ $row->tax_number }}</span>
                    </td>
                    <td>
                      <span class="badge {{ $statusBadge[$row->status] ?? 'badge-secondary' }}">
                        {{ $statusLabels[$row->status] ?? $row->status }}
                      </span>
                    </td>
                    <td>
                      <span class="badge badge-light border">Toplam: {{ (int) ($row->listings_total ?? 0) }}</span>
                      <span class="badge badge-success">Yayında: {{ (int) ($row->listings_active ?? 0) }}</span>
                    </td>
                    <td>
                      {{ $row->submitted_at ? $row->submitted_at->format('d.m.Y H:i') : '—' }}
                    </td>
                    <td>
                      @if($row->reviewer)
                        {{ $row->reviewer->name }}<br>
                        <span class="text-muted small">{{ $row->reviewed_at ? $row->reviewed_at->format('d.m.Y') : '' }}</span>
                      @else
                        —
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted">Kayıt bulunamadı.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $rows->links() }}
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
