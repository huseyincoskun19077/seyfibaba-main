@extends('admin.master_layout')
@section('title')
<title>İkinci El Raporlar</title>
@endsection

@section('admin-content')
@php
  $statusLabels = [
    'open' => 'Açık',
    'reviewing' => 'İnceleniyor',
    'resolved' => 'Çözüldü',
    'dismissed' => 'Reddedildi',
  ];
  $statusBadge = [
    'open' => 'badge-warning',
    'reviewing' => 'badge-info',
    'resolved' => 'badge-success',
    'dismissed' => 'badge-secondary',
  ];

  $reasonLabels = [
    'spam' => 'Spam',
    'scam' => 'Dolandırıcılık',
    'harassment' => 'Taciz',
    'illegal' => 'Yasadışı',
    'other' => 'Diğer',
  ];

  $subjectLabels = [
    'listing' => 'İlan',
    'message' => 'Mesaj',
    'user' => 'Kullanıcı',
  ];
@endphp
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İkinci El Raporlar</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.second-hand.index') }}">İkinci El</a></div>
        <div class="breadcrumb-item">Raporlar</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card mb-3">
        <div class="card-body py-3">
          <form method="GET" action="{{ route('admin.second-hand.reports') }}">
            <div class="row">
              <div class="col-md-3">
                <div class="form-group mb-2">
                  <label>Durum</label>
                  <select class="form-control" name="status">
                    <option value="all">Tümü</option>
                    <option value="open" {{ request('status')==='open' ? 'selected' : '' }}>Açık</option>
                    <option value="reviewing" {{ request('status')==='reviewing' ? 'selected' : '' }}>İnceleniyor</option>
                    <option value="resolved" {{ request('status')==='resolved' ? 'selected' : '' }}>Çözüldü</option>
                    <option value="dismissed" {{ request('status')==='dismissed' ? 'selected' : '' }}>Reddedildi</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-2">
                  <label>Konu</label>
                  <select class="form-control" name="subject_type">
                    <option value="all">Tümü</option>
                    <option value="listing" {{ request('subject_type')==='listing' ? 'selected' : '' }}>İlan</option>
                    <option value="message" {{ request('subject_type')==='message' ? 'selected' : '' }}>Mesaj</option>
                    <option value="user" {{ request('subject_type')==='user' ? 'selected' : '' }}>Kullanıcı</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-2">
                  <label>Sebep</label>
                  <select class="form-control" name="reason">
                    <option value="all">Tümü</option>
                    @foreach($reasonLabels as $k => $lbl)
                      <option value="{{ $k }}" {{ request('reason')===$k ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-2">
                  <label>Arama</label>
                  <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Detay / not / id">
                </div>
              </div>
            </div>
            <div class="d-flex" style="gap:8px;">
              <button class="btn btn-primary" type="submit"><i class="fas fa-filter mr-1"></i> Filtrele</button>
              <a class="btn btn-outline-secondary" href="{{ route('admin.second-hand.reports') }}">Sıfırla</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h4>Rapor Kuyruğu</h4></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Konu</th>
                  <th>Sebep</th>
                  <th>Raporlayan</th>
                  <th>Bağlam</th>
                  <th>Durum</th>
                  <th style="min-width:360px;">İşlem</th>
                </tr>
              </thead>
              <tbody>
                @forelse($reports as $r)
                  <tr>
                    <td>{{ $r->id }}</td>
                    <td>
                      <span class="badge badge-light">{{ $subjectLabels[$r->subject_type] ?? $r->subject_type }}</span><br>
                      <span class="text-muted small">subject_id: <code>{{ $r->subject_id }}</code></span>
                    </td>
                    <td>
                      <span class="badge badge-dark">{{ $reasonLabels[$r->reason] ?? $r->reason }}</span>
                      @if($r->details)
                        <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($r->details, 120) }}</div>
                      @endif
                    </td>
                    <td class="small">
                      <strong>{{ optional($r->reporter)->name ?? '-' }}</strong><br>
                      <span class="text-muted">{{ optional($r->reporter)->email ?? '-' }}</span>
                    </td>
                    <td class="small">
                      @if($r->listing_id)
                        <div><strong>İlan:</strong> #{{ $r->listing_id }} {{ optional($r->listing)->title ? '— '.optional($r->listing)->title : '' }}</div>
                      @endif
                      @if($r->conversation_id)
                        <div><strong>Konuşma:</strong> #{{ $r->conversation_id }}</div>
                      @endif
                      @if($r->message_id)
                        <div><strong>Mesaj:</strong> #{{ $r->message_id }}</div>
                      @endif
                    </td>
                    <td>
                      <span class="badge {{ $statusBadge[$r->status] ?? 'badge-secondary' }}">
                        {{ $statusLabels[$r->status] ?? $r->status }}
                      </span>
                      <div class="text-muted small mt-1">
                        {{ optional($r->handled_at)->format('d.m.Y H:i') ?: '-' }}
                        @if($r->handler)
                          | {{ $r->handler->name }}
                        @endif
                      </div>
                    </td>
                    <td>
                      <form method="POST" action="{{ route('admin.second-hand.reports.update', $r->id) }}" class="mb-2">
                        @csrf
                        @method('PUT')
                        <div class="form-row">
                          <div class="col-md-5 mb-2">
                            <select class="form-control form-control-sm" name="status" required>
                              <option value="open" {{ $r->status==='open' ? 'selected' : '' }}>Açık</option>
                              <option value="reviewing" {{ $r->status==='reviewing' ? 'selected' : '' }}>İnceleniyor</option>
                              <option value="resolved" {{ $r->status==='resolved' ? 'selected' : '' }}>Çözüldü</option>
                              <option value="dismissed" {{ $r->status==='dismissed' ? 'selected' : '' }}>Reddedildi</option>
                            </select>
                          </div>
                          <div class="col-md-7 mb-2">
                            <input class="form-control form-control-sm" name="admin_note" value="{{ $r->admin_note }}" placeholder="Admin notu (opsiyonel)">
                          </div>
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">
                          <i class="fas fa-save mr-1"></i> Kaydet
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">Henüz rapor yok.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            {{ $reports->links() }}
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
