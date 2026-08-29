@extends('admin.master_layout')
@section('title')
<title>İkinci El — Argo Denemeleri</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İkinci El — Argo Denemeleri</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.second-hand.index') }}">İkinci El</a></div>
        <div class="breadcrumb-item">Argo Denemeleri</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card mb-3">
        <div class="card-body py-3">
          <form method="GET" action="{{ route('admin.second-hand.moderation-logs') }}">
            <div class="row">
              <div class="col-md-9">
                <div class="form-group mb-2">
                  <label>Arama</label>
                  <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Mesaj içeriği...">
                </div>
              </div>
              <div class="col-md-3 d-flex align-items-end" style="gap:8px;">
                <button class="btn btn-primary" type="submit"><i class="fas fa-filter mr-1"></i> Filtrele</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.second-hand.moderation-logs') }}">Sıfırla</a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h4>Kayıtlar</h4></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Tarih</th>
                  <th>Conversation</th>
                  <th>Listing</th>
                  <th>Gönderen</th>
                  <th>Alıcı</th>
                  <th>Eşleşen</th>
                  <th>İçerik</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ optional($r->created_at)->format('d.m.Y H:i:s') }}</td>
                    <td>{{ $r->conversation_id }}</td>
                    <td>{{ $r->listing_id }}</td>
                    <td>{{ $r->sender_id }}</td>
                    <td>{{ $r->receiver_id }}</td>
                    <td><span class="badge badge-danger">{{ $r->matched ?: '-' }}</span></td>
                    <td style="max-width:420px; white-space:normal;">{{ $r->body }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted">Kayıt yok.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $rows->links() }}
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

