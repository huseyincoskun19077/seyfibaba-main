@extends('seller.master_layout')
@section('title')
<title>Sıkça Sorulan Sorular</title>
@endsection

@section('seller-content')
<style>
  .sf-wrap { max-width: 860px; margin: 0 auto; }
  .sf-hero {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: #fff; border-radius: 20px; padding: 28px 32px; margin-bottom: 24px;
  }
  .sf-hero h1 { font-size: 1.6rem; font-weight: 700; margin: 0 0 8px; }
  .sf-hero p { margin: 0; opacity: .92; line-height: 1.55; }
  .sf-section { margin-bottom: 20px; }
  .sf-section-title {
    font-weight: 700; color: #334155; font-size: 1.05rem;
    margin-bottom: 12px; display: flex; align-items: center; gap: 10px;
  }
  .sf-section-title i { color: #6366f1; width: 22px; text-align: center; }
  .sf-card { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(15,23,42,.06); overflow: hidden; }
  .sf-q {
    width: 100%; text-align: left; background: #fff; border: none; border-bottom: 1px solid #f1f5f9;
    padding: 16px 20px; font-weight: 600; color: #1e293b; cursor: pointer;
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
  }
  .sf-q:hover { background: #f8fafc; }
  .sf-q i.fa-chevron-down { color: #94a3b8; transition: transform .2s; font-size: .85rem; }
  .sf-q[aria-expanded="true"] i.fa-chevron-down { transform: rotate(180deg); }
  .sf-a {
    padding: 0 20px 18px; color: #475569; line-height: 1.65; font-size: .95rem;
  }
  .sf-a strong { color: #334155; }
  .sf-contact {
    background: #eef2ff; border-radius: 16px; padding: 20px 24px; margin-top: 28px;
    border: 1px solid #c7d2fe;
  }
</style>

@php
    $faq = config('seller_faq');
@endphp

<div class="main-content">
  <section class="section">
    <div class="section-header d-none d-md-flex">
      <h1>Sıkça Sorulan Sorular</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">Panel</a></div>
        <div class="breadcrumb-item">SSS</div>
      </div>
    </div>

    <div class="section-body sf-wrap">
      <div class="sf-hero">
        <h1><i class="fas fa-question-circle mr-2"></i> Satıcı SSS</h1>
        <p>{{ $faq['intro'] ?? '' }}</p>
      </div>

      @foreach ($faq['sections'] ?? [] as $section)
        <div class="sf-section">
          <div class="sf-section-title">
            <i class="fas {{ $section['icon'] ?? 'fa-info-circle' }}"></i>
            {{ $section['title'] }}
          </div>
          <div class="sf-card">
            <div class="accordion" id="faqSection{{ $loop->index }}">
              @foreach ($section['items'] ?? [] as $item)
                @php $uid = 'faq-' . $loop->parent->index . '-' . $loop->index; @endphp
                <div>
                  <button class="sf-q" type="button" data-toggle="collapse" data-target="#{{ $uid }}"
                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="{{ $uid }}">
                    <span>{{ $item['q'] }}</span>
                    <i class="fas fa-chevron-down"></i>
                  </button>
                  <div id="{{ $uid }}" class="collapse {{ $loop->first ? 'show' : '' }}" data-parent="#faqSection{{ $loop->parent->index }}">
                    <div class="sf-a">{!! $item['a'] !!}</div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endforeach

      <div class="sf-contact">
        <strong><i class="fas fa-headset text-primary mr-1"></i> Sorunuz listede yok mu?</strong>
        <p class="mb-2 mt-2 text-muted" style="font-size:.95rem;">Ekibimize yazın — en kısa sürede dönüş yapalım.</p>
        <a href="{{ route('seller.contact-admin') }}" class="btn btn-primary btn-sm mr-2"><i class="fas fa-envelope mr-1"></i> Admin'e Mesaj</a>
        <a href="tel:08503035073" class="btn btn-outline-primary btn-sm">0850 303 5073</a>
      </div>
    </div>
  </section>
</div>
@endsection
