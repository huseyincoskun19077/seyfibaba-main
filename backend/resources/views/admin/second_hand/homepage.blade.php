@extends('admin.master_layout')
@section('title')
<title>İkinci El Anasayfa</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İkinci El Anasayfa</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.second-hand.index') }}">İkinci El</a></div>
        <div class="breadcrumb-item">Anasayfa</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header">
          <h4>ikinciel.seyfibaba.com görünümü</h4>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.second-hand.homepage.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
              <label>Başlık</label>
              <input
                type="text"
                name="homepage_title"
                class="form-control"
                value="{{ old('homepage_title', $agreement->homepage_title ?: 'Kuaför malzemeleri al/sat') }}"
                required
              >
            </div>

            <div class="form-group">
              <label>Açıklama</label>
              <textarea name="homepage_subtitle" rows="4" class="form-control">{{ old('homepage_subtitle', $agreement->homepage_subtitle) }}</textarea>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Birincil buton</label>
                  <input
                    type="text"
                    name="homepage_cta_primary"
                    class="form-control"
                    value="{{ old('homepage_cta_primary', $agreement->homepage_cta_primary ?: 'İlan ver') }}"
                  >
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>İkincil buton</label>
                  <input
                    type="text"
                    name="homepage_cta_secondary"
                    class="form-control"
                    value="{{ old('homepage_cta_secondary', $agreement->homepage_cta_secondary ?: 'İlanları gör') }}"
                  >
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Kapak görseli</label>
              @if($agreement->homepage_image)
                <div class="mb-2">
                  <img src="{{ asset($agreement->homepage_image) }}" alt="" style="max-height:160px;border-radius:8px;">
                </div>
                <label class="d-block">
                  <input type="checkbox" name="homepage_image_remove" value="1"> Görseli kaldır
                </label>
              @endif
              <input type="file" name="homepage_image" class="form-control" accept="image/jpeg,image/png,image/webp">
              <small class="text-muted">JPG, PNG veya WEBP. En fazla 4 MB.</small>
            </div>

            <div class="form-group">
              <label class="d-block">
                <input type="hidden" name="homepage_show_categories" value="0">
                <input type="checkbox" name="homepage_show_categories" value="1" {{ old('homepage_show_categories', $agreement->homepage_show_categories ?? true) ? 'checked' : '' }}>
                Kategorileri göster
              </label>
              <label class="d-block mt-2">
                <input type="hidden" name="homepage_show_featured" value="0">
                <input type="checkbox" name="homepage_show_featured" value="1" {{ old('homepage_show_featured', $agreement->homepage_show_featured ?? true) ? 'checked' : '' }}>
                Öne çıkan / senin için bloklarını göster
              </label>
            </div>

            <button type="submit" class="btn btn-primary">Kaydet</button>
            <a href="https://ikinciel.seyfibaba.com" target="_blank" class="btn btn-outline-secondary">Siteyi aç</a>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h4>Slider (ikinciel.seyfibaba.com anasayfa)</h4>
        </div>
        <div class="card-body">
          <p class="text-muted mb-3">Bu görseller header altında döner. Mobilde 180px, masaüstünde daha yüksek gösterilir. Sıra numarası küçükten büyüğe.</p>
          <form method="POST" action="{{ route('admin.second-hand.homepage.sliders.store') }}" enctype="multipart/form-data" class="mb-4">
            @csrf
            @if(!empty($editSlider))
              <input type="hidden" name="id" value="{{ $editSlider->id }}">
            @endif
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Görsel {{ empty($editSlider) ? '*' : '' }}</label>
                  @if(!empty($editSlider?->image))
                    <div class="mb-2">
                      <img src="{{ asset($editSlider->image) }}" alt="" style="max-height:80px;border-radius:6px;">
                    </div>
                  @endif
                  <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" {{ empty($editSlider) ? 'required' : '' }}>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Başlık</label>
                  <input type="text" name="title" class="form-control" value="{{ old('title', $editSlider->title ?? '') }}">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Alt başlık</label>
                  <input type="text" name="subtitle" class="form-control" value="{{ old('subtitle', $editSlider->subtitle ?? '') }}">
                </div>
              </div>
              <div class="col-md-5">
                <div class="form-group">
                  <label>Link</label>
                  <input type="text" name="link" class="form-control" placeholder="/ikinci-el veya https://..." value="{{ old('link', $editSlider->link ?? '') }}">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Sıra</label>
                  <input type="number" name="serial" class="form-control" min="0" value="{{ old('serial', $editSlider->serial ?? 1) }}">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label class="d-block">&nbsp;</label>
                  <label class="mt-2">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" {{ old('status', $editSlider->status ?? true) ? 'checked' : '' }}>
                    Aktif
                  </label>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label class="d-block">&nbsp;</label>
                  <button type="submit" class="btn btn-primary">{{ !empty($editSlider) ? 'Güncelle' : 'Slider ekle' }}</button>
                  @if(!empty($editSlider))
                    <a href="{{ route('admin.second-hand.homepage') }}" class="btn btn-light">İptal</a>
                  @endif
                </div>
              </div>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Görsel</th>
                  <th>Başlık</th>
                  <th>Sıra</th>
                  <th>Durum</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse(($sliders ?? []) as $slider)
                  <tr>
                    <td>
                      @if($slider->image)
                        <img src="{{ asset($slider->image) }}" alt="" style="height:48px;border-radius:6px;">
                      @endif
                    </td>
                    <td>{{ $slider->title }}</td>
                    <td>{{ $slider->serial }}</td>
                    <td>{{ $slider->status ? 'Aktif' : 'Pasif' }}</td>
                    <td class="text-right">
                      <a href="{{ route('admin.second-hand.homepage', ['edit_slider' => $slider->id]) }}" class="btn btn-sm btn-primary">Düzenle</a>
                      <form method="POST" action="{{ route('admin.second-hand.homepage.sliders.delete', $slider->id) }}" class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-muted">Henüz slider yok. Yukarıdan ekleyin.</td>
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
