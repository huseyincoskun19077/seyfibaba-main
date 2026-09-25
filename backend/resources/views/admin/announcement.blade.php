@extends('admin.master_layout')
@section('title')
<title>Anasayfa Kampanya Popup</title>
@endsection
@section('admin-content')
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>Anasayfa Kampanya Popup</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">Anasayfa Kampanya Popup</div>
            </div>
          </div>

          <div class="section-body">
                <div class="col">
                  <div class="card">
                    <div class="card-header">
                      <h4>Web + mobil giriş tanıtımı</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                          <strong>Nasıl çalışır?</strong>
                          <ul class="mb-0 pl-3">
                            <li><strong>Aktif:</strong> Kullanıcı siteye / uygulamaya ilk girişte popup görür. Sayfada gezip anasayfaya dönünce tekrar çıkmaz.</li>
                            <li><strong>Pasif:</strong> Popup hiç gösterilmez.</li>
                            <li><strong>Bir daha gösterme:</strong> Kullanıcı seçerse, aşağıdaki gün sayısı kadar tekrar gösterilmez.</li>
                          </ul>
                        </div>

                        <form action="{{ route('admin.announcement-update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="">Popup durumu (Aktif / Pasif)</label>
                                <div>
                                    @if ($announcement->status == 1)
                                        <input id="status_toggle" type="checkbox" checked data-toggle="toggle" data-on="Aktif" data-off="Pasif" data-onstyle="success" data-offstyle="danger" name="status">
                                    @else
                                        <input id="status_toggle" type="checkbox" data-toggle="toggle" data-on="Aktif" data-off="Pasif" data-onstyle="success" data-offstyle="danger" name="status">
                                    @endif
                                </div>
                            </div>

                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group">
                                  <label>Web’de göster</label>
                                  <div>
                                    <input type="checkbox" name="show_on_web" value="1" {{ (int)($announcement->show_on_web ?? 1) === 1 ? 'checked' : '' }}>
                                    Site anasayfa girişi
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group">
                                  <label>Mobil uygulamada göster</label>
                                  <div>
                                    <input type="checkbox" name="show_on_mobile" value="1" {{ (int)($announcement->show_on_mobile ?? 1) === 1 ? 'checked' : '' }}>
                                    Uygulama açılışında anasayfa
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div class="form-group">
                                <label for="">Mevcut görsel</label>
                                <div>
                                    @if(!empty($announcement->image))
                                      <img src="{{ asset($announcement->image) }}" style="max-width:280px;border-radius:8px;border:1px solid #ddd;" alt="">
                                    @else
                                      <span class="text-muted">Henüz görsel yok</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="">Yeni görsel (ürün / kampanya afişi)</label>
                                <input type="file" class="form-control-file" name="image" accept=".jpg,.jpeg,.png,.webp">
                                <small class="form-text text-muted d-block mt-2">
                                  <strong>Önerilen boyut:</strong> <code>800 × 1000 px</code> (dikey, mobil + web için ideal)<br>
                                  Alternatif yatay: <code>812 × 509 px</code><br>
                                  Format: JPG / PNG / WEBP · Maks. 5 MB · Yüksek kalite için 2x: <code>1600 × 2000 px</code>
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="">Başlık (opsiyonel)</label>
                                <input type="text" class="form-control" name="title" value="{{ old('title', $announcement->title) }}" placeholder="Örn: Yeni ürünümüz geldi">
                            </div>

                            <div class="form-group">
                                <label for="">Açıklama (opsiyonel)</label>
                                <textarea name="description" cols="30" rows="4" class="form-control" placeholder="Kısa tanıtım metni">{{ old('description', $announcement->description) }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="">Web bağlantısı (ürün veya kampanya)</label>
                                <input type="text" class="form-control" name="link" value="{{ old('link', $announcement->link) }}" placeholder="/urun/urun-slug veya https://...">
                                <small class="form-text text-muted">Örnek: <code>/urun/keratin-samuan</code> veya tam URL</small>
                            </div>

                            <div class="form-group">
                                <label for="">Mobil bağlantı (opsiyonel)</label>
                                <input type="text" class="form-control" name="mobile_link" value="{{ old('mobile_link', $announcement->mobile_link) }}" placeholder="Boş bırakılırsa web bağlantısı kullanılır">
                                <small class="form-text text-muted">Ürün slug veya yol: <code>urun-slug</code> / <code>/urun/urun-slug</code></small>
                            </div>

                            <div class="form-group">
                                <label for="">Buton metni (opsiyonel)</label>
                                <input type="text" class="form-control" name="cta_text" value="{{ old('cta_text', $announcement->cta_text) }}" placeholder="Örn: İncele">
                            </div>

                            <div class="form-group">
                                <label for="">“Bir daha gösterme” süresi (gün)</label>
                                <input type="number" class="form-control" name="expired_date" min="1" max="365" value="{{ old('expired_date', $announcement->expired_date ?? 7) }}">
                                <small class="form-text text-muted">Kullanıcı “Bir daha gösterme” derse bu kadar gün gizlenir.</small>
                            </div>

                            <button class="btn btn-primary" type="submit">{{__('admin.Update')}}</button>
                        </form>
                    </div>
                  </div>
                </div>
          </div>
        </section>
      </div>
@endsection
