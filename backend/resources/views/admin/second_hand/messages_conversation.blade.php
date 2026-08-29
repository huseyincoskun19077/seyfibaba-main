@extends('admin.master_layout')
@section('title')
<title>İkinci El — Konuşma #{{ $conversation->id }}</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Konuşma #{{ $conversation->id }}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.second-hand.index') }}">İkinci El</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.second-hand.messages') }}">Mesajlar</a></div>
        <div class="breadcrumb-item">Konuşma #{{ $conversation->id }}</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card mb-3">
        <div class="card-body">
          <div><strong>Listing:</strong> {{ optional($conversation->listing)->title ?? '—' }} (ID: {{ $conversation->listing_id }})</div>
          <div class="text-muted small"><strong>Seller:</strong> {{ $conversation->seller_id }} · <strong>Buyer:</strong> {{ $conversation->buyer_id }}</div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h4>Mesajlar</h4></div>
        <div class="card-body">
          <div style="max-height:70vh; overflow:auto;">
            @forelse($messages as $m)
              <div class="p-3 mb-2 border rounded {{ (int)$m->sender_id === (int)$conversation->seller_id ? 'bg-light' : '' }}">
                <div class="small text-muted">
                  <strong>Sender:</strong> {{ $m->sender_id }}
                  <span class="mx-2">·</span>
                  {{ optional($m->created_at)->format('d.m.Y H:i:s') }}
                </div>
                @if(!empty($m->body))
                  <div style="white-space:pre-wrap;">{{ $m->body }}</div>
                @endif
                @if($m->attachments && $m->attachments->count())
                  <div class="mt-2">
                    @foreach($m->attachments as $a)
                      <a class="badge badge-info" target="_blank" href="{{ $a->url }}">
                        {{ $a->original_name ?: 'Ek' }}
                      </a>
                    @endforeach
                  </div>
                @endif
              </div>
            @empty
              <div class="text-muted">Mesaj yok.</div>
            @endforelse
          </div>
          {{ $messages->links() }}
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

