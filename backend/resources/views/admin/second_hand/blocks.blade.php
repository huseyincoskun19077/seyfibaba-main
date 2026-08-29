@extends('admin.master_layout')
@section('title')
<title>İkinci El Engeller</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İkinci El — Engeller</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.second-hand.index') }}">İkinci El</a></div>
        <div class="breadcrumb-item">Engeller</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card mb-3">
        <div class="card-body py-3">
          <form method="GET" action="{{ route('admin.second-hand.blocks') }}">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group mb-2">
                  <label>User ID</label>
                  <input class="form-control" name="user_id" value="{{ request('user_id') }}" placeholder="blocker veya blocked id">
                </div>
              </div>
              <div class="col-md-8 d-flex align-items-end" style="gap:8px;">
                <button class="btn btn-primary" type="submit"><i class="fas fa-filter mr-1"></i> Filtrele</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.second-hand.blocks') }}">Sıfırla</a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h4>Engelleme Kayıtları</h4></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Engelleyen</th>
                  <th>Engellenen</th>
                  <th>Sebep</th>
                  <th>Tarih</th>
                </tr>
              </thead>
              <tbody>
                @forelse($blocks as $b)
                  <tr>
                    <td>{{ $b->id }}</td>
                    <td class="small">
                      <div><strong>{{ optional($b->blocker)->name ?? '-' }}</strong></div>
                      <div class="text-muted">{{ optional($b->blocker)->email ?? '' }}</div>
                      <div class="text-muted">ID: {{ $b->blocker_id }}</div>
                    </td>
                    <td class="small">
                      <div><strong>{{ optional($b->blocked)->name ?? '-' }}</strong></div>
                      <div class="text-muted">{{ optional($b->blocked)->email ?? '' }}</div>
                      <div class="text-muted">ID: {{ $b->blocked_id }}</div>
                    </td>
                    <td class="small">{{ $b->reason ?: '—' }}</td>
                    <td class="small">{{ optional($b->created_at)->format('d.m.Y H:i') ?: '-' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Engel kaydı yok.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $blocks->links() }}
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

