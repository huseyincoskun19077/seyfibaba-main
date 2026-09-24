@extends('admin.master_layout')
@section('title')
<title>Stories</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Stories (Instagram şeridi)</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">Stories</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header">
          <h4>Header altı story yönetimi</h4>
        </div>
        <div class="card-body">
          <p class="text-muted mb-3">
            Ürün vitrini tipinde web’de hover, mobilde tıklamada ürünler story gibi açılır. Bağlantı tipinde sayfaya gider.
            <strong>Web’de göster / Mobil’de göster</strong> tikleri ile platform seçin (ikisini de açık bırakabilirsiniz).
            <strong>Web bağlantısı</strong> site, <strong>Mobil bağlantı</strong> uygulama içindir (boşsa web linki kullanılır).
            Listedeki satırları <strong>sürükle-bırak</strong> ile sıralayabilirsiniz.
          </p>

          <div class="alert alert-light border mb-4">
            <strong>İzlenme özeti:</strong>
            Tabloda her story için toplam / web / mobil görüntüleme, üye &amp; misafir, benzersiz izleyici ve ortalama ilerleme görünür.
            <em>Migrate:</em> <code>php artisan migrate --force</code> (<code>story_views</code>)
          </div>

          <form method="POST" action="{{ route('admin.story.store') }}" enctype="multipart/form-data" class="mb-4">
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
            @if(!empty($editStory))
              <input type="hidden" name="id" value="{{ $editStory->id }}">
            @endif
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label>Başlık *</label>
                  <input type="text" name="title" class="form-control" required value="{{ old('title', !empty($editStory) ? $editStory->title : '') }}">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Görsel</label>
                  @if(!empty($editStory) && !empty($editStory->image))
                    <div class="mb-2">
                      <img src="{{ asset($editStory->image) }}" alt="" style="max-height:72px;border-radius:50%;width:72px;object-fit:cover;">
                    </div>
                  @endif
                  <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Tip *</label>
                  <select name="type" id="story-type" class="form-control" required>
                    @foreach($types as $key => $label)
                      <option value="{{ $key }}" {{ old('type', !empty($editStory) ? $editStory->type : 'product_feed') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-md-2" id="story-feed-wrap">
                <div class="form-group">
                  <label>Ürün kaynağı</label>
                  <select name="feed" class="form-control">
                    @foreach($feeds as $key => $label)
                      <option value="{{ $key }}" {{ old('feed', !empty($editStory) ? $editStory->feed : 'popular') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Sıra <small class="text-muted">(veya sürükle)</small></label>
                  <input type="number" name="serial" class="form-control" min="0" value="{{ old('serial', !empty($editStory) ? $editStory->serial : (($stories->max('serial') ?? 0) + 1)) }}">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Web bağlantısı</label>
                  <input type="text" name="link" class="form-control" placeholder="/satici veya https://..." value="{{ old('link', !empty($editStory) ? $editStory->link : '') }}">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Mobil bağlantı</label>
                  <input type="text" name="mobile_link" class="form-control" placeholder="https://kuafortedarik.com/satici-kayit veya boş" value="{{ old('mobile_link', !empty($editStory) ? ($editStory->mobile_link ?? '') : '') }}">
                  <small class="text-muted">Uygulama; boşsa web bağlantısı kullanılır</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>Tümünü gör URL (web)</label>
                  <input type="text" name="see_all_url" class="form-control" placeholder="/products?highlight=..." value="{{ old('see_all_url', !empty($editStory) ? $editStory->see_all_url : '') }}">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label class="d-block">&nbsp;</label>
                  <label class="mt-2 d-block">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" {{ old('status', !empty($editStory) ? (int) $editStory->status : 1) ? 'checked' : '' }}>
                    Aktif
                  </label>
                  <label class="mt-2 d-block">
                    <input type="hidden" name="show_on_web" value="0">
                    <input type="checkbox" name="show_on_web" value="1" {{ old('show_on_web', !empty($editStory) ? (int) ($editStory->show_on_web ?? 1) : 1) ? 'checked' : '' }}>
                    Web’de göster
                  </label>
                  <label class="mt-2 d-block">
                    <input type="hidden" name="show_on_mobile" value="0">
                    <input type="checkbox" name="show_on_mobile" value="1" {{ old('show_on_mobile', !empty($editStory) ? (int) ($editStory->show_on_mobile ?? 1) : 1) ? 'checked' : '' }}>
                    Mobil’de göster
                  </label>
                </div>
              </div>
              <div class="col-md-12">
                <button type="submit" class="btn btn-primary">{{ !empty($editStory) ? 'Güncelle' : 'Ekle' }}</button>
                @if(!empty($editStory))
                  <a href="{{ route('admin.story.index') }}" class="btn btn-light">İptal</a>
                @endif
              </div>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table table-striped" id="storyTable">
              <thead>
                <tr>
                  <th style="width:36px;"></th>
                  <th style="width:56px;">Sıra</th>
                  <th>Görsel</th>
                  <th>Başlık</th>
                  <th>Tip</th>
                  <th>Kaynak / Web / Mobil</th>
                  <th>Durum</th>
                  <th>Web</th>
                  <th>Mobil</th>
                  <th>İzlenme</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="sortable-body">
                @forelse($stories as $index => $story)
                  @php $st = $storyStats[$story->id] ?? [
                    'total_views'=>0,'web_views'=>0,'mobile_views'=>0,
                    'auth_views'=>0,'guest_views'=>0,'unique_users'=>0,
                    'unique_auth'=>0,'unique_guests'=>0,'completions'=>0,'avg_progress_pct'=>0
                  ]; @endphp
                  <tr data-id="{{ $story->id }}">
                    <td class="drag-handle" title="Sürükle"><i class="fas fa-grip-vertical"></i></td>
                    <td class="sort-number">{{ $index + 1 }}</td>
                    <td>
                      @if($story->image)
                        <img src="{{ asset($story->image) }}" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td>{{ $story->title }}</td>
                    <td>{{ $types[$story->type] ?? $story->type }}</td>
                    <td>
                      @if($story->type === 'product_feed')
                        {{ $feeds[$story->feed] ?? $story->feed }}
                        @if($story->see_all_url)
                          <br><small class="text-muted">see-all: {{ $story->see_all_url }}</small>
                        @endif
                      @else
                        <div>Web: {{ $story->link ?: '—' }}</div>
                        <small class="text-muted">Mobil: {{ $story->mobile_link ?: '(web ile aynı)' }}</small>
                      @endif
                    </td>
                    <td>{{ $story->status ? 'Aktif' : 'Pasif' }}</td>
                    <td>{{ ($story->show_on_web ?? true) ? '✓' : '—' }}</td>
                    <td>{{ ($story->show_on_mobile ?? true) ? '✓' : '—' }}</td>
                    <td style="min-width:220px;font-size:12px;line-height:1.45;">
                      <div><strong>Toplam:</strong> {{ $st['total_views'] }}</div>
                      <div>Web: {{ $st['web_views'] }} · Mobil: {{ $st['mobile_views'] }}</div>
                      <div>Üye izlenme: {{ $st['auth_views'] }} · Misafir: {{ $st['guest_views'] }}</div>
                      <div>Benzersiz: {{ $st['unique_users'] }}
                        <small class="text-muted">(üye {{ $st['unique_auth'] }} / misafir {{ $st['unique_guests'] }})</small>
                      </div>
                      <div>Tamamlanan: {{ $st['completions'] }} · Ort. ilerleme: %{{ $st['avg_progress_pct'] }}</div>
                    </td>
                    <td class="text-right sort-actions">
                      <button type="button" class="btn btn-light btn-sm btn-move-up" title="Yukarı"><i class="fas fa-arrow-up"></i></button>
                      <button type="button" class="btn btn-light btn-sm btn-move-down" title="Aşağı"><i class="fas fa-arrow-down"></i></button>
                      <a href="{{ route('admin.story.index', ['edit' => $story->id]) }}" class="btn btn-sm btn-primary">Düzenle</a>
                      <form action="{{ route('admin.story.destroy', $story->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr class="dataTables_empty"><td colspan="11" class="text-center text-muted">Kayıt yok</td></tr>
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
(function () {
  var typeEl = document.getElementById('story-type');
  var feedWrap = document.getElementById('story-feed-wrap');
  function sync() {
    if (!typeEl || !feedWrap) return;
    feedWrap.style.display = typeEl.value === 'product_feed' ? '' : 'none';
  }
  if (typeEl) {
    typeEl.addEventListener('change', sync);
    sync();
  }
})();
</script>
@include('admin.partials.category_sortable_script', [
  'tableId' => 'storyTable',
  'reorderUrl' => route('admin.story.reorder'),
])
@endsection
