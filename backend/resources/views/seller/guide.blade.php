@extends('seller.master_layout')
@section('title')
<title>Satıcı Şartlar ve Tanıtım</title>
@endsection

@section('seller-content')
@php
  $guide = config('seller_guide');
@endphp
<style>
  .sg-wrap { max-width: 920px; margin: 0 auto; }
  .sg-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 55%, #b45309 140%);
    color: #fff; border-radius: 20px; padding: 28px 32px; margin-bottom: 20px;
  }
  .sg-hero h1 { font-size: 1.55rem; font-weight: 800; margin: 0 0 8px; }
  .sg-hero p { margin: 0; opacity: .93; line-height: 1.6; }
  .sg-chips { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 22px; }
  .sg-chip {
    background: #fff; border-radius: 14px; padding: 14px 16px;
    box-shadow: 0 4px 18px rgba(15,23,42,.06); border: 1px solid #e2e8f0;
  }
  .sg-chip i { color: #b45309; margin-bottom: 6px; }
  .sg-chip strong { display: block; color: #0f172a; margin-bottom: 4px; }
  .sg-chip span { color: #64748b; font-size: .88rem; line-height: 1.45; display: block; }
  .sg-section {
    background: #fff; border-radius: 16px; padding: 20px 22px; margin-bottom: 14px;
    border: 1px solid #e2e8f0; box-shadow: 0 2px 12px rgba(15,23,42,.04);
  }
  .sg-section h2 {
    font-size: 1.12rem; font-weight: 700; color: #0f172a; margin: 0 0 10px;
    display: flex; align-items: center; gap: 10px;
  }
  .sg-section h2 i { color: #b45309; width: 22px; text-align: center; }
  .sg-section p { color: #475569; line-height: 1.65; margin-bottom: 10px; }
  .sg-section ul { margin: 0; padding-left: 18px; color: #334155; }
  .sg-section li { margin-bottom: 6px; line-height: 1.5; }
  .sg-contact {
    background: #fff7ed; border: 1px solid #fed7aa; border-radius: 16px;
    padding: 18px 20px; margin-top: 8px;
  }
</style>

<div class="main-content">
  <section class="section">
    <div class="section-header d-none d-md-flex">
      <h1>Satıcı Şartlar ve Tanıtım</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">Panel</a></div>
        <div class="breadcrumb-item">Şartlar ve Tanıtım</div>
      </div>
    </div>

    <div class="section-body">
      <div class="sg-wrap">
        <div class="sg-hero">
          <h1>{{ $guide['title'] ?? 'Satıcı Şartlar ve Tanıtım' }}</h1>
          <p class="mb-2">{{ $guide['subtitle'] ?? '' }}</p>
          <p>{{ $guide['hero'] ?? '' }}</p>
        </div>

        <div class="sg-chips">
          @foreach(($guide['highlights'] ?? []) as $item)
            <div class="sg-chip">
              <i class="fas {{ $item['icon'] ?? 'fa-info-circle' }}"></i>
              <strong>{{ $item['title'] ?? '' }}</strong>
              <span>{{ $item['text'] ?? '' }}</span>
            </div>
          @endforeach
        </div>

        @foreach(($guide['sections'] ?? []) as $section)
          <div class="sg-section" id="{{ $section['id'] ?? '' }}">
            <h2><i class="fas {{ $section['icon'] ?? 'fa-circle' }}"></i> {{ $section['title'] ?? '' }}</h2>
            <p>{!! $section['body'] ?? '' !!}</p>
            @if(!empty($section['bullets']))
              <ul>
                @foreach($section['bullets'] as $bullet)
                  <li>{!! $bullet !!}</li>
                @endforeach
              </ul>
            @endif
          </div>
        @endforeach

        @php $contact = $guide['contact'] ?? []; @endphp
        <div class="sg-contact">
          <strong>{{ $contact['title'] ?? 'Destek' }}</strong>
          <p class="mb-1 mt-1 text-muted">{{ $contact['text'] ?? '' }}</p>
          <div>
            <a href="tel:{{ preg_replace('/\s+/', '', $contact['phone'] ?? '') }}">{{ $contact['phone'] ?? '' }}</a>
            ·
            <a href="mailto:{{ $contact['email'] ?? '' }}">{{ $contact['email'] ?? '' }}</a>
            ·
            <a href="{{ route('seller.faq') }}">SSS</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
