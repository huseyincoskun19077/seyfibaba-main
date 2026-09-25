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
          <div class="alert alert-info">
            <ul class="mb-0 pl-3">
              <li><strong>Anasayfa:</strong> yalnızca burada seçtiğiniz kategori / ürün / satıcıdan <strong>{{ (int)($edit->home_limit ?? 12) }}</strong> ürün gösterilir.</li>
              <li><strong>Tümünü gör:</strong> aynı seçimler + (tikliyse) tüm ürünlerden yüksek görüntülenme.</li>
              <li>Kategori, alt kategori ve child kategorileri listeden işaretleyin — ID yazmanıza gerek yok.</li>
            </ul>
          </div>

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
                  <label>Anasayfa adedi</label>
                  <input type="number" name="home_limit" class="form-control" min="4" max="24"
                    value="{{ old('home_limit', $edit->home_limit ?? 12) }}">
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

            <div class="form-group">
              <label class="d-block">
                <input type="hidden" name="include_high_views" value="0">
                <input type="checkbox" name="include_high_views" value="1"
                  {{ old('include_high_views', !empty($edit) ? (int)($edit->include_high_views ?? 0) : 0) ? 'checked' : '' }}>
                Tüm ürünlerden görüntülenmesi yüksek olanları da göster
              </label>
              <small class="text-muted">Anasayfada görünmez. Sadece <strong>Tümünü gör</strong> sayfasında, admin seçimlerinin altına eklenir.</small>
            </div>

            <h6 class="mt-3">Mevcut işletmeler — kategoriler</h6>
            <div class="border rounded p-3 mb-3" style="max-height:320px;overflow:auto;background:#fafafa">
              @php $selCats = old('category_ids', $selectedCats ?? []); @endphp
              @forelse($categoryTree as $cat)
                <div class="mb-2">
                  <label class="font-weight-bold mb-1 d-block">
                    <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                      {{ in_array((int)$cat->id, array_map('intval', (array)$selCats), true) ? 'checked' : '' }}>
                    {{ $cat->name }} <small class="text-muted">#{{ $cat->id }}</small>
                  </label>
                  @foreach($cat->activeSubCategories as $sub)
                    <label class="d-block mb-0" style="margin-left:18px">
                      <input type="checkbox" name="category_ids[]" value="{{ $sub->id }}"
                        {{ in_array((int)$sub->id, array_map('intval', (array)$selCats), true) ? 'checked' : '' }}>
                      └ {{ $sub->name }} <small class="text-muted">alt #{{ $sub->id }}</small>
                    </label>
                    @foreach($sub->activeChildCategories as $child)
                      <label class="d-block mb-0" style="margin-left:36px">
                        <input type="checkbox" name="category_ids[]" value="{{ $child->id }}"
                          {{ in_array((int)$child->id, array_map('intval', (array)$selCats), true) ? 'checked' : '' }}>
                        └─ {{ $child->name }} <small class="text-muted">child #{{ $child->id }}</small>
                      </label>
                    @endforeach
                  @endforeach
                </div>
              @empty
                <p class="text-muted mb-0">Aktif kategori yok</p>
              @endforelse
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Ürün ID’leri (opsiyonel — önce bunlar, max anasayfa adedi)</label>
                  <input type="text" name="product_ids" class="form-control" placeholder="101,202,303"
                    value="{{ old('product_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->product_ids)) : '') }}">
                  <small class="text-muted">Boş bırakırsanız seçili kategorilerden otomatik dolar.</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Satıcı (vendor) ID’leri (opsiyonel)</label>
                  <input type="text" name="vendor_ids" class="form-control" placeholder="3,8"
                    value="{{ old('vendor_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->vendor_ids)) : '') }}">
                </div>
              </div>
            </div>

            <h6 class="mt-3">Yeni dükkan açanlar (opsiyonel)</h6>
            <div class="border rounded p-3 mb-3" style="max-height:240px;overflow:auto;background:#fafafa">
              @php $selOpen = old('opening_category_ids', $selectedOpeningCats ?? []); @endphp
              @foreach($categoryTree as $cat)
                <div class="mb-2">
                  <label class="font-weight-bold mb-1 d-block">
                    <input type="checkbox" name="opening_category_ids[]" value="{{ $cat->id }}"
                      {{ in_array((int)$cat->id, array_map('intval', (array)$selOpen), true) ? 'checked' : '' }}>
                    {{ $cat->name }}
                  </label>
                  @foreach($cat->activeSubCategories as $sub)
                    <label class="d-block mb-0" style="margin-left:18px">
                      <input type="checkbox" name="opening_category_ids[]" value="{{ $sub->id }}"
                        {{ in_array((int)$sub->id, array_map('intval', (array)$selOpen), true) ? 'checked' : '' }}>
                      └ {{ $sub->name }}
                    </label>
                    @foreach($sub->activeChildCategories as $child)
                      <label class="d-block mb-0" style="margin-left:36px">
                        <input type="checkbox" name="opening_category_ids[]" value="{{ $child->id }}"
                          {{ in_array((int)$child->id, array_map('intval', (array)$selOpen), true) ? 'checked' : '' }}>
                        └─ {{ $child->name }}
                      </label>
                    @endforeach
                  @endforeach
                </div>
              @endforeach
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Açılış ürün ID</label>
                  <input type="text" name="opening_product_ids" class="form-control"
                    value="{{ old('opening_product_ids', !empty($edit) ? implode(',', $edit->decodeIds($edit->opening_product_ids)) : '') }}">
                </div>
              </div>
              <div class="col-md-6">
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

          <details class="mb-3">
            <summary>Satıcı listesi (vendor ID)</summary>
            <ul class="small mb-0" style="max-height:180px;overflow:auto">
              @foreach($vendors as $v)
                <li>{{ $v->id }} — {{ $v->shop_name }}</li>
              @endforeach
            </ul>
          </details>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Sektör</th>
                  <th>Başlık</th>
                  <th>Kategori</th>
                  <th>Ürün</th>
                  <th>Anasayfa</th>
                  <th>Yüksek view</th>
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
                    <td>{{ $row->home_limit ?? 12 }}</td>
                    <td>{{ $row->include_high_views ? 'Evet' : 'Hayır' }}</td>
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
