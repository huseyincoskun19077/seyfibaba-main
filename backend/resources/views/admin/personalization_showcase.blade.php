@extends('admin.master_layout')
@section('title')
<title>Sana Özel Vitrin</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Sana Özel (sektör vitrini)</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">Sana Özel</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header">
          <h4>Sektöre göre anasayfa ürünleri</h4>
        </div>
        <div class="card-body">
          <p class="text-muted mb-3">
            Kullanıcı girişte erkek kuaförü / güzellik salonu / nail art vb. seçince mobilde <strong>Sana Özel</strong> şeridinde
            burada tanımladığınız kategori, ürün ve satıcı ürünleri listelenir.
            <br>Yeni dükkan açanlar (<em>yakında açacağım / planlıyorum</em>) için ayrı ID alanları doluysa onlar kullanılır.
            Boşsa popüler ürünler gösterilir.
            <br><small>ID’leri virgülle yazın: <code>12,45,78</code></small>
          </p>

          <form method="POST" action="{{ route('admin.personalization-showcase.store') }}" class="mb-4">
            @csrf
            @if(!empty($edit))
              <input type="hidden" name="id" value="{{ $edit->id }}">
            @endif
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label>Sektör *</label>
                  <select name="business_type" class="form-control" required {{ !empty($edit) ? 'disabled' : '' }}>
                    @foreach($types as $key => $label)
                      <option value="{{ $key }}" {{ old('business_type', $edit->business_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                  @if(!empty($edit))
                    <input type="hidden" name="business_type" value="{{ $edit->business_type }}">
                  @endif
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Başlık</label>
                  <input type="text" name="title" class="form-control" value="{{ old('title', $edit->title ?? 'Sana Özel') }}" required>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label class="d-block">&nbsp;</label>
                  <label class="mt-2">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" {{ old('status', !empty($edit) ? (int)$edit->status : 1) ? 'checked' : '' }}>
                    Aktif
                  </label>
                </div>
              </div>
            </div>

            <h6 class="mt-2">Mevcut işletmeler</h6>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Kategori ID’leri</label>
                  <input type="text" name="category_ids" class="form-control" placeholder="1,5,12"
                    value="{{ old('category_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->category_ids)) : '') }}">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Ürün ID’leri (öne çıkar)</label>
                  <input type="text" name="product_ids" class="form-control" placeholder="101,202"
                    value="{{ old('product_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->product_ids)) : '') }}">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Satıcı (vendor) ID’leri</label>
                  <input type="text" name="vendor_ids" class="form-control" placeholder="3,8"
                    value="{{ old('vendor_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->vendor_ids)) : '') }}">
                </div>
              </div>
            </div>

            <h6 class="mt-2">Yeni dükkan açanlar (opsiyonel)</h6>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Açılış kategori ID</label>
                  <input type="text" name="opening_category_ids" class="form-control"
                    value="{{ old('opening_category_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->opening_category_ids)) : '') }}">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Açılış ürün ID</label>
                  <input type="text" name="opening_product_ids" class="form-control"
                    value="{{ old('opening_product_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->opening_product_ids)) : '') }}">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Açılış satıcı ID</label>
                  <input type="text" name="opening_vendor_ids" class="form-control"
                    value="{{ old('opening_vendor_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->opening_vendor_ids)) : '') }}">
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary">{{ !empty($edit) ? 'Güncelle' : 'Kaydet' }}</button>
            @if(!empty($edit))
              <a href="{{ route('admin.personalization-showcase.index') }}" class="btn btn-light">İptal</a>
            @endif
          </form>

          <div class="row mb-3">
            <div class="col-md-6">
              <details>
                <summary>Kategori listesi (ID)</summary>
                <ul class="small mb-0" style="max-height:180px;overflow:auto">
                  @foreach($categories as $c)
                    <li>{{ $c->id }} — {{ $c->name }}</li>
                  @endforeach
                </ul>
              </details>
            </div>
            <div class="col-md-6">
              <details>
                <summary>Satıcı listesi (vendor ID)</summary>
                <ul class="small mb-0" style="max-height:180px;overflow:auto">
                  @foreach($vendors as $v)
                    <li>{{ $v->id }} — {{ $v->shop_name }}</li>
                  @endforeach
                </ul>
              </details>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Sektör</th>
                  <th>Başlık</th>
                  <th>Kategori</th>
                  <th>Ürün</th>
                  <th>Satıcı</th>
                  <th>Açılış</th>
                  <th>Durum</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $row)
                  <tr>
                    <td>{{ $types[$row->business_type] ?? $row->business_type }}</td>
                    <td>{{ $row->title }}</td>
                    <td><small>{{ implode(', ', $row->decodeIds($row->category_ids)) ?: '—' }}</small></td>
                    <td><small>{{ implode(', ', $row->decodeIds($row->product_ids)) ?: '—' }}</small></td>
                    <td><small>{{ implode(', ', $row->decodeIds($row->vendor_ids)) ?: '—' }}</small></td>
                    <td>
                      <small>
                        @php
                          $o = array_merge(
                            $row->decodeIds($row->opening_category_ids),
                            $row->decodeIds($row->opening_product_ids),
                            $row->decodeIds($row->opening_vendor_ids)
                          );
                        @endphp
                        {{ $o ? 'Tanımlı' : '—' }}
                      </small>
                    </td>
                    <td>{{ $row->status ? 'Aktif' : 'Pasif' }}</td>
                    <td class="text-right">
                      <a href="{{ route('admin.personalization-showcase.index', ['edit' => $row->id]) }}" class="btn btn-sm btn-primary">Düzenle</a>
                      <form action="{{ route('admin.personalization-showcase.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Sil</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="text-center text-muted">Henüz vitrin yok</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
