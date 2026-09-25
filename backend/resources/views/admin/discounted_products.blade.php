@extends('admin.master_layout')
@section('title')
<title>İndirimli Ürünler</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İndirimli Ürünler</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.home-blocks.index') }}">Anasayfa Blokları</a></div>
        <div class="breadcrumb-item">İndirimli Ürünler</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-lg-3">
          <div class="card">
            <div class="card-header"><h4>Satıcılar (indirimli ürün sayısı)</h4></div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center {{ $vendorId === null || $vendorId === '' ? 'active' : '' }}">
                  <a href="{{ route('admin.home-blocks.discounted', array_filter(['q' => $q ?: null])) }}" class="{{ $vendorId === null || $vendorId === '' ? 'text-white' : '' }}">Tümü</a>
                  <span class="badge badge-{{ $vendorId === null || $vendorId === '' ? 'light' : 'primary' }} badge-pill">{{ $sellersWithDiscount->sum('cnt') }}</span>
                </li>
                @foreach($sellersWithDiscount as $row)
                  @php
                    $vid = (int) ($row->vendor_id ?? 0);
                    $isPlatform = $vid === 0;
                    $shop = $isPlatform ? 'Platform (admin)' : ($vendors[$vid]->shop_name ?? ('Satıcı #'.$vid));
                    $email = $isPlatform ? '' : ($vendors[$vid]->email ?? '');
                    $active = (string)$vendorId === (string)$vid || ($vendorId === '0' && $isPlatform);
                  @endphp
                  <li class="list-group-item {{ $active ? 'active' : '' }}">
                    <a href="{{ route('admin.home-blocks.discounted', array_filter(['vendor_id' => $vid, 'q' => $q ?: null])) }}"
                       class="d-block {{ $active ? 'text-white' : '' }}">
                      <strong>{{ $shop }}</strong>
                      @if($email)
                        <br><small class="{{ $active ? 'text-white-50' : 'text-muted' }}">{{ $email }}</small>
                      @endif
                    </a>
                    <span class="badge badge-{{ $active ? 'light' : 'primary' }} badge-pill">{{ $row->cnt }}</span>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>

        <div class="col-lg-9">
          <div class="card">
            <div class="card-header">
              <h4>Ürünler — anasayfa indirimli bölümü için seç</h4>
            </div>
            <div class="card-body">
              <div class="alert alert-info">
                İşaretlediğiniz ürünler anasayfadaki <strong>İndirimli Ürünler</strong> bloğunda görünür.
                Hiç seçmezseniz otomatik indirimli listesi kullanılır.
                @if(!empty($block))
                  <br><small>Blok: #{{ $block->id }} — {{ $block->title }} ({{ count($homepageIds) }} seçili)</small>
                @endif
              </div>

              <form method="GET" action="{{ route('admin.home-blocks.discounted') }}" class="mb-3">
                @if($vendorId !== null && $vendorId !== '')
                  <input type="hidden" name="vendor_id" value="{{ $vendorId }}">
                @endif
                <div class="input-group">
                  <input type="text" name="q" class="form-control" placeholder="Ürün adı ara…" value="{{ $q }}">
                  <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Ara</button>
                  </div>
                </div>
              </form>

              <form method="POST" action="{{ route('admin.home-blocks.discounted.save') }}" id="discounted-home-form">
                @csrf
                <div class="mb-3">
                  <button type="submit" class="btn btn-success">Seçilenleri anasayfaya kaydet</button>
                  <button type="button" class="btn btn-light" id="btn-select-page">Bu sayfadakileri işaretle</button>
                  <button type="button" class="btn btn-outline-secondary" id="btn-clear-page">Bu sayfayı temizle</button>
                </div>

                <div class="table-responsive">
                  <table class="table table-striped table-sm">
                    <thead>
                      <tr>
                        <th style="width:40px">Anasayfa</th>
                        <th>Ürün</th>
                        <th>Satıcı</th>
                        <th>Fiyat</th>
                        <th>İndirimli</th>
                        <th>%</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($products as $p)
                        @php
                          $price = (float) $p->price;
                          $offer = (float) $p->offer_price;
                          $pct = $price > 0 ? round((1 - ($offer / $price)) * 100) : 0;
                          $sellerName = $p->seller?->shop_name ?: 'Platform';
                          $checked = in_array((int)$p->id, array_map('intval', $homepageIds), true);
                        @endphp
                        <tr>
                          <td>
                            <input type="checkbox" name="product_ids[]" value="{{ $p->id }}" class="home-pick"
                              {{ $checked ? 'checked' : '' }}>
                          </td>
                          <td>
                            <strong>{{ $p->short_name ?: $p->name }}</strong>
                            <br><small class="text-muted">#{{ $p->id }}</small>
                          </td>
                          <td>
                            {{ $sellerName }}
                            @if($p->seller?->email)
                              <br><small class="text-muted">{{ $p->seller->email }}</small>
                            @endif
                          </td>
                          <td>{{ number_format($price, 2, ',', '.') }} ₺</td>
                          <td class="text-success font-weight-bold">{{ number_format($offer, 2, ',', '.') }} ₺</td>
                          <td><span class="badge badge-danger">%{{ $pct }}</span></td>
                        </tr>
                      @empty
                        <tr><td colspan="6" class="text-center text-muted">İndirimli ürün yok</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

                {{-- Sayfa dışı mevcut seçimleri koru --}}
                @php $pageIds = $products->getCollection()->pluck('id')->map(fn($id)=>(int)$id)->all(); @endphp
                @foreach($homepageIds as $hid)
                  @if(!in_array((int)$hid, $pageIds, true))
                    <input type="hidden" name="product_ids[]" value="{{ $hid }}" class="home-pick-persist">
                  @endif
                @endforeach

                <div class="mt-2">
                  {{ $products->links() }}
                </div>

                <div class="mt-3">
                  <button type="submit" class="btn btn-success">Seçilenleri anasayfaya kaydet</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<script>
(function(){
  var form = document.getElementById('discounted-home-form');
  if(!form) return;
  document.getElementById('btn-select-page')?.addEventListener('click', function(){
    form.querySelectorAll('input.home-pick').forEach(function(c){ c.checked = true; });
  });
  document.getElementById('btn-clear-page')?.addEventListener('click', function(){
    form.querySelectorAll('input.home-pick').forEach(function(c){ c.checked = false; });
  });
})();
</script>
@endsection
