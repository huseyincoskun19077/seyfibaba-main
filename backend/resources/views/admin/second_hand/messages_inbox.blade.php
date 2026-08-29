@extends('admin.master_layout')
@section('title')
<title>İkinci El — Mesajlar</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İkinci El — Mesajlar</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.second-hand.index') }}">İkinci El</a></div>
        <div class="breadcrumb-item">Mesajlar</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card mb-3">
        <div class="card-body py-3">
          <form method="GET" action="{{ route('admin.second-hand.messages') }}">
            <div class="row">
              <div class="col-md-3">
                <div class="form-group mb-2">
                  <label>Listing ID</label>
                  <input class="form-control" name="listing_id" value="{{ request('listing_id') }}" placeholder="örn: 12">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-2">
                  <label>User ID</label>
                  <input class="form-control" name="user_id" value="{{ request('user_id') }}" placeholder="örn: 130">
                </div>
              </div>
              <div class="col-md-6 d-flex align-items-end" style="gap:8px;">
                <button class="btn btn-primary" type="submit"><i class="fas fa-filter mr-1"></i> Filtrele</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.second-hand.messages') }}">Sıfırla</a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h4>Konuşmalar</h4></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>İlan</th>
                  <th>Seller</th>
                  <th>Buyer</th>
                  <th>Son mesaj</th>
                  <th>Mesaj sayısı</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($conversations as $c)
                  <tr>
                    <td>{{ $c->id }}</td>
                    <td>
                      <div><strong>{{ optional($c->listing)->title ?? '—' }}</strong></div>
                      <div class="text-muted small">Listing ID: {{ $c->listing_id }}</div>
                    </td>
                    <td>{{ $c->seller_id }}</td>
                    <td>{{ $c->buyer_id }}</td>
                    <td class="small">
                      <div class="text-muted">{{ optional($c->last_message_at)->format('d.m.Y H:i') ?: '-' }}</div>
                      <div class="text-muted" style="max-width:320px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $c->last_message_preview ?: '—' }}
                      </div>
                    </td>
                    <td>{{ (int) ($c->messages_count ?? 0) }}</td>
                    <td class="text-right">
                      <a class="btn btn-sm btn-primary" href="{{ route('admin.second-hand.messages.conversation', $c->id) }}">
                        Aç
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted">Konuşma yok.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $conversations->links() }}
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

