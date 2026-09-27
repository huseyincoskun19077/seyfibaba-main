@extends('admin.master_layout')
@section('title')
<title>{{ $segment->name }} — Müşteri Alanı</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>{{ $segment->name }}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.customer-segments.index') }}">Müşteri Alanları</a></div>
        <div class="breadcrumb-item">Düzenle</div>
      </div>
    </div>
    <div class="section-body">
      <div class="row">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h4>Alan ayarları</h4></div>
            <div class="card-body">
              <form method="POST" action="{{ route('admin.customer-segments.update', $segment->id) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                  <label>Görünen ad</label>
                  <input type="text" name="name" class="form-control" value="{{ old('name', $segment->name) }}" required>
                </div>
                <div class="form-group">
                  <label>Kısa ad</label>
                  <input type="text" name="short_name" class="form-control" value="{{ old('short_name', $segment->short_name) }}">
                </div>
                <div class="form-group">
                  <label>Açıklama</label>
                  <textarea name="description" class="form-control" rows="3">{{ old('description', $segment->description) }}</textarea>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-4">
                    <label>Sıra</label>
                    <input type="number" name="serial" class="form-control" value="{{ old('serial', $segment->serial) }}">
                  </div>
                  <div class="form-group col-md-4">
                    <label>Satıcı çeşitliliği</label>
                    <input type="number" name="vendor_diversity" class="form-control" value="{{ old('vendor_diversity', $segment->vendor_diversity) }}" min="1" max="20">
                  </div>
                  <div class="form-group col-md-4">
                    <label>Vitrin ürün limiti</label>
                    <input type="number" name="home_product_limit" class="form-control" value="{{ old('home_product_limit', $segment->home_product_limit) }}" min="4" max="48">
                  </div>
                </div>
                <div class="form-group">
                  <label>Profil işletme türü anahtarı</label>
                  <input type="text" name="business_type_key" class="form-control" value="{{ old('business_type_key', $segment->business_type_key) }}" placeholder="Örn. female_hairdresser (kod)">
                  <small class="text-muted">Profildeki işletme türü kodu ile eşleme (müşteriye gösterilmez)</small>
                </div>
                <div class="form-group">
                  <label><input type="checkbox" name="is_active" value="1" {{ $segment->is_active ? 'checked' : '' }}> Aktif</label>
                  <label class="ml-3"><input type="checkbox" name="show_on_guest_home" value="1" {{ $segment->show_on_guest_home ? 'checked' : '' }}> Misafir ana sayfada göster</label>
                  <label class="ml-3"><input type="checkbox" name="is_primary_home" value="1" {{ $segment->is_primary_home ? 'checked' : '' }}> Birincil giriş kartı</label>
                </div>
                <button type="submit" class="btn btn-primary">Kaydet</button>
              </form>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h4>Kategori eşlemesi</h4></div>
            <div class="card-body">
              <form method="POST" action="{{ route('admin.customer-segments.taxonomy.store', $segment->id) }}" class="mb-3">
                @csrf
                <div class="form-group">
                  <label>Üst kategori</label>
                  <select name="category_id" class="form-control">
                    <option value="">—</option>
                    @foreach($categories as $c)
                      <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group">
                  <label>Alt kategori</label>
                  <select name="sub_category_id" class="form-control">
                    <option value="">—</option>
                    @foreach($subCategories as $s)
                      <option value="{{ $s->id }}">{{ $s->category->name ?? '?' }} › {{ $s->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group">
                  <label>Child kategori ID (opsiyonel)</label>
                  <input type="number" name="child_category_id" class="form-control" placeholder="ID">
                </div>
                <button type="submit" class="btn btn-success btn-sm">Eşleme ekle</button>
              </form>
              <ul class="list-group">
                @forelse($segment->taxonomies as $t)
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                      {{ $t->category->name ?? '—' }}
                      @if($t->subCategory) › {{ $t->subCategory->name }} @endif
                      @if($t->child_category_id) › #{{ $t->child_category_id }} @endif
                    </span>
                    <form method="POST" action="{{ route('admin.customer-segments.taxonomy.destroy', [$segment->id, $t->id]) }}" onsubmit="return confirm('Kaldırılsın mı?');">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-danger btn-sm">Sil</button>
                    </form>
                  </li>
                @empty
                  <li class="list-group-item text-muted">Henüz eşleme yok.</li>
                @endforelse
              </ul>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><h4>Vitrin / zorla ürün</h4></div>
            <div class="card-body">
              <form method="POST" action="{{ route('admin.customer-segments.featured.store', $segment->id) }}" class="form-inline mb-3">
                @csrf
                <input type="number" name="product_id" class="form-control mr-2" placeholder="Ürün ID" required>
                <label class="mr-2"><input type="checkbox" name="is_featured" value="1" checked> Vitrin</label>
                <button class="btn btn-primary btn-sm">Ekle</button>
              </form>
              <ul class="list-group">
                @forelse($featured as $f)
                  <li class="list-group-item d-flex justify-content-between">
                    <span>#{{ $f->product_id }} {{ $f->product->name ?? '' }}</span>
                    <form method="POST" action="{{ route('admin.customer-segments.featured.destroy', [$segment->id, $f->id]) }}">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-sm btn-danger">Kaldır</button>
                    </form>
                  </li>
                @empty
                  <li class="list-group-item text-muted">Vitrin ürünü yok.</li>
                @endforelse
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
