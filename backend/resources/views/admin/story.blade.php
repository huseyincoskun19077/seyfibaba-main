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
            Ürün vitrini tipinde hover/tıklamada ürünler yatay kayar. Bağlantı tipinde doğrudan sayfaya gider.
            Görsel yüklemezseniz sarı halka içinde başlık harfi gösterilir.
            Listedeki satırları <strong>sürükle-bırak</strong> ile sıralayabilirsiniz; site üst şeridinde aynı sıra görünür.
          </p>

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
              <div class="col-md-4">
                <div class="form-group">
                  <label>Bağlantı (link tipi veya yedek)</label>
                  <input type="text" name="link" class="form-control" placeholder="/satici veya https://..." value="{{ old('link', !empty($editStory) ? $editStory->link : '') }}">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Tümünü gör URL</label>
                  <input type="text" name="see_all_url" class="form-control" placeholder="/products?highlight=..." value="{{ old('see_all_url', !empty($editStory) ? $editStory->see_all_url : '') }}">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label class="d-block">&nbsp;</label>
                  <label class="mt-2">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" {{ old('status', !empty($editStory) ? (int) $editStory->status : 1) ? 'checked' : '' }}>
                    Aktif
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
                  <th>Kaynak / Link</th>
                  <th>Durum</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="sortable-body">
                @forelse($stories as $index => $story)
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
                      @else
                        {{ $story->link ?: '—' }}
                      @endif
                    </td>
                    <td>{{ $story->status ? 'Aktif' : 'Pasif' }}</td>
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
                  <tr class="dataTables_empty"><td colspan="8" class="text-center text-muted">Kayıt yok</td></tr>
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
