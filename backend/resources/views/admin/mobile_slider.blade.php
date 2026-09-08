@extends('admin.master_layout')
@section('title')
<title>Mobil Slider</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Mobil Slider</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.slider.index') }}">{{__('admin.Slider')}}</a></div>
        <div class="breadcrumb-item">Mobil</div>
      </div>
    </div>

    <div class="section-body">
      <a href="{{ route('admin.slider.index') }}" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left"></i> Web Slider</a>

      <div class="card">
        <div class="card-header">
          <h4>Mobil uygulama anasayfa slider</h4>
        </div>
        <div class="card-body">
          <p class="text-muted mb-3">
            Bu görseller yalnızca mobil uygulamada görünür. Başlık / alt başlık / link opsiyoneldir.
          </p>

          <form method="POST" action="{{ route('admin.mobile-slider.store') }}" enctype="multipart/form-data" class="mb-4">
            @csrf
            @if ($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif
            @if(!empty($editSlider))
              <input type="hidden" name="id" value="{{ $editSlider->id }}">
            @endif
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Görsel {{ empty($editSlider) ? '*' : '' }}</label>
                  @if(!empty($editSlider) && !empty($editSlider->image))
                    <div class="mb-2">
                      <img src="{{ asset($editSlider->image) }}" alt="" style="max-height:80px;border-radius:6px;">
                    </div>
                  @endif
                  <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" {{ empty($editSlider) ? 'required' : '' }}>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Başlık (opsiyonel)</label>
                  <input type="text" name="title" class="form-control" value="{{ old('title', !empty($editSlider) ? $editSlider->title : '') }}">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Alt başlık (opsiyonel)</label>
                  <input type="text" name="subtitle" class="form-control" value="{{ old('subtitle', !empty($editSlider) ? $editSlider->subtitle : '') }}">
                </div>
              </div>
              <div class="col-md-5">
                <div class="form-group">
                  <label>Bağlantı (opsiyonel)</label>
                  <input type="text" name="link" class="form-control" value="{{ old('link', !empty($editSlider) ? $editSlider->link : '') }}">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Ürün / kategori slug (opsiyonel)</label>
                  <input type="text" name="product_slug" class="form-control" value="{{ old('product_slug', !empty($editSlider) ? $editSlider->product_slug : '') }}">
                </div>
              </div>
              <div class="col-md-1">
                <div class="form-group">
                  <label>Sıra</label>
                  <input type="number" name="serial" class="form-control" min="0" value="{{ old('serial', !empty($editSlider) ? $editSlider->serial : 1) }}">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label class="d-block">&nbsp;</label>
                  <label class="mt-2">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" {{ old('status', !empty($editSlider) ? (int) $editSlider->status : 1) ? 'checked' : '' }}>
                    Aktif
                  </label>
                </div>
              </div>
              <div class="col-md-12">
                <button type="submit" class="btn btn-primary">{{ !empty($editSlider) ? 'Güncelle' : 'Mobil\'e ekle' }}</button>
                @if(!empty($editSlider))
                  <a href="{{ route('admin.mobile-slider.index') }}" class="btn btn-light">İptal</a>
                @endif
              </div>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Görsel</th>
                  <th>Başlık</th>
                  <th>Bağlantı</th>
                  <th>Sıra</th>
                  <th>Durum</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($sliders as $slider)
                  <tr>
                    <td>
                      @if($slider->image)
                        <img src="{{ asset($slider->image) }}" alt="" style="height:48px;border-radius:6px;">
                      @endif
                    </td>
                    <td>{{ $slider->title ?: '—' }}</td>
                    <td>
                      @if($slider->link)
                        <small>{{ \Illuminate\Support\Str::limit($slider->link, 40) }}</small>
                      @elseif($slider->product_slug)
                        <small class="text-muted">{{ $slider->product_slug }}</small>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td>{{ $slider->serial }}</td>
                    <td>{{ $slider->status ? 'Aktif' : 'Pasif' }}</td>
                    <td class="text-right">
                      <a href="{{ route('admin.mobile-slider.index', ['edit' => $slider->id]) }}" class="btn btn-sm btn-primary">Düzenle</a>
                      <form method="POST" action="{{ route('admin.mobile-slider.destroy', $slider->id) }}" class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-muted">Henüz mobil slider yok. Yukarıdan ekleyin.</td>
                  </tr>
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
