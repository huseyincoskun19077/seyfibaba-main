@extends('admin.master_layout')
@section('title')
<title>Anasayfa Blokları</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Anasayfa Blokları</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">Anasayfa Blokları</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header">
          <h4>Sınırsız bölüm — sürükle bırak sıralama</h4>
        </div>
        <div class="card-body">
          <div class="alert alert-info">
            <ul class="mb-0 pl-3">
              <li>Stories + Sana Özel üstte sabittir. Bu listedeki bloklar onların <strong>altında</strong> sırayla gelir.</li>
              <li><strong>Kampanya görseli:</strong> yalnızca resim + link (ürün/kategori URL). Önerilen boyut: <code>800 × 320 px</code> (yatay afiş).</li>
              <li><strong>Ürün listesi:</strong> popüler / indirimli / öne çıkan / yeni / en iyi / hafta sonu / özel ID.</li>
              <li>Hafta sonu ve özel listede ürün ID’lerini girin: <code>12,45,78</code></li>
            </ul>
          </div>

          <form method="POST" action="{{ route('admin.home-blocks.store') }}" enctype="multipart/form-data" class="mb-4">
            @csrf
            @if(!empty($edit))
              <input type="hidden" name="id" value="{{ $edit->id }}">
            @endif
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label>Başlık *</label>
                  <input type="text" name="title" class="form-control" required value="{{ old('title', $edit->title ?? '') }}">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Tip *</label>
                  <select name="type" id="hb-type" class="form-control" required>
                    @foreach($types as $key => $label)
                      <option value="{{ $key }}" {{ old('type', $edit->type ?? 'product_feed') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-md-3" id="hb-feed-wrap">
                <div class="form-group">
                  <label>Ürün kaynağı</label>
                  <select name="feed" class="form-control">
                    @foreach($feeds as $key => $label)
                      <option value="{{ $key }}" {{ old('feed', $edit->feed ?? 'popular') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Adet</label>
                  <input type="number" name="limit_count" class="form-control" min="1" max="48" value="{{ old('limit_count', $edit->limit_count ?? 12) }}">
                </div>
              </div>
            </div>

            <div class="row" id="hb-campaign-wrap">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Kampanya görseli</label>
                  @if(!empty($edit->image))
                    <div class="mb-2"><img src="{{ asset($edit->image) }}" alt="" style="max-height:80px;border-radius:8px;"></div>
                  @endif
                  <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                  <small class="text-muted">Önerilen: 800×320 px · JPG/PNG/WEBP · max 5MB</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Web link</label>
                  <input type="text" name="link" class="form-control" placeholder="/products?category=... veya /urun/slug"
                    value="{{ old('link', $edit->link ?? '') }}">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Mobil link (opsiyonel)</label>
                  <input type="text" name="mobile_link" class="form-control" value="{{ old('mobile_link', $edit->mobile_link ?? '') }}">
                </div>
              </div>
            </div>

            <div class="row" id="hb-products-wrap">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Ürün ID’leri (hafta sonu / özel / pin)</label>
                  <input type="text" name="product_ids" class="form-control" placeholder="101,202,303"
                    value="{{ old('product_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->product_ids)) : '') }}">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Kategori ID’leri (filtre, opsiyonel)</label>
                  <input type="text" name="category_ids" class="form-control" placeholder="1,5,12"
                    value="{{ old('category_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->category_ids)) : '') }}">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Tümünü gör URL</label>
                  <input type="text" name="see_all_url" class="form-control" placeholder="/products?highlight=..."
                    value="{{ old('see_all_url', $edit->see_all_url ?? '') }}">
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="mr-3">
                <input type="hidden" name="status" value="0">
                <input type="checkbox" name="status" value="1" {{ old('status', !empty($edit) ? (int)$edit->status : 1) ? 'checked' : '' }}> Aktif
              </label>
              <label class="mr-3">
                <input type="hidden" name="show_on_web" value="0">
                <input type="checkbox" name="show_on_web" value="1" {{ old('show_on_web', !empty($edit) ? (int)$edit->show_on_web : 1) ? 'checked' : '' }}> Web
              </label>
              <label>
                <input type="hidden" name="show_on_mobile" value="0">
                <input type="checkbox" name="show_on_mobile" value="1" {{ old('show_on_mobile', !empty($edit) ? (int)$edit->show_on_mobile : 1) ? 'checked' : '' }}> Mobil
              </label>
            </div>

            <button type="submit" class="btn btn-primary">{{ !empty($edit) ? 'Güncelle' : 'Ekle' }}</button>
            @if(!empty($edit))
              <a href="{{ route('admin.home-blocks.index') }}" class="btn btn-light">İptal</a>
            @endif
          </form>

          <div class="table-responsive">
            <table class="table table-striped" id="homeBlocksTable">
              <thead>
                <tr>
                  <th style="width:36px"></th>
                  <th>#</th>
                  <th>Başlık</th>
                  <th>Tip</th>
                  <th>Kaynak</th>
                  <th>Görsel</th>
                  <th>Platform</th>
                  <th>Durum</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="home-blocks-sortable">
                @forelse($blocks as $row)
                  <tr data-id="{{ $row->id }}">
                    <td class="text-muted" style="cursor:grab">☰</td>
                    <td>{{ $row->serial }}</td>
                    <td>{{ $row->title }}</td>
                    <td><small>{{ $types[$row->type] ?? $row->type }}</small></td>
                    <td><small>{{ $row->feed ? ($feeds[$row->feed] ?? $row->feed) : '—' }}</small></td>
                    <td>
                      @if($row->image)
                        <img src="{{ asset($row->image) }}" alt="" style="height:36px;border-radius:4px;">
                      @else
                        —
                      @endif
                    </td>
                    <td>
                      <small>
                        {{ $row->show_on_web ? 'Web' : '' }}
                        {{ $row->show_on_web && $row->show_on_mobile ? '+' : '' }}
                        {{ $row->show_on_mobile ? 'Mobil' : '' }}
                      </small>
                    </td>
                    <td>{{ $row->status ? 'Aktif' : 'Pasif' }}</td>
                    <td class="text-right">
                      <a href="{{ route('admin.home-blocks.index', ['edit' => $row->id]) }}" class="btn btn-sm btn-primary">Düzenle</a>
                      <form action="{{ route('admin.home-blocks.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Sil</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="9" class="text-center text-muted">Blok yok</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<script>
(function(){
  function syncType(){
    var t = document.getElementById('hb-type');
    if(!t) return;
    var type = t.value;
    var feed = document.getElementById('hb-feed-wrap');
    var camp = document.getElementById('hb-campaign-wrap');
    var prod = document.getElementById('hb-products-wrap');
    if(feed) feed.style.display = type === 'product_feed' ? '' : 'none';
    if(camp) camp.style.display = (type === 'campaign') ? '' : 'none';
    if(prod) prod.style.display = (type === 'product_feed' || type === 'all_products' || type === 'category_grid') ? '' : 'none';
  }
  var t = document.getElementById('hb-type');
  if(t){ t.addEventListener('change', syncType); syncType(); }
})();
</script>
@include('admin.partials.category_sortable_script', [
  'tableId' => 'homeBlocksTable',
  'sortableBodyId' => 'home-blocks-sortable',
  'reorderUrl' => route('admin.home-blocks.reorder'),
])
@endsection
