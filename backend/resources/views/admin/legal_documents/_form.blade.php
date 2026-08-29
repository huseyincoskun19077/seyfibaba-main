<form action="{{ $action }}" method="POST">
  @csrf
  @if($method === 'PUT')
    @method('PUT')
  @endif

  <div class="row">
    <div class="form-group col-md-8">
      <label>Başlık <span class="text-danger">*</span></label>
      <input type="text" name="title" class="form-control" value="{{ old('title', $document->title ?? '') }}" required>
    </div>
    <div class="form-group col-md-4">
      <label>Slug <span class="text-danger">*</span></label>
      <input type="text" name="slug" class="form-control" value="{{ old('slug', $document->slug ?? '') }}" required>
      <small class="text-muted">Örn: terms → /legal/terms</small>
    </div>
    <div class="form-group col-md-3">
      <label>Versiyon <span class="text-danger">*</span></label>
      <input type="text" name="version" class="form-control" value="{{ old('version', $document->version ?? '1.0') }}" required>
    </div>
    <div class="form-group col-md-3">
      <label>Sıra</label>
      <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $document->sort_order ?? 0) }}" min="0">
    </div>
    <div class="form-group col-md-3">
      <label>Kategori</label>
      <select name="category" class="form-control">
        @foreach(['legal' => 'Yasal', 'seller' => 'Satıcı', 'corporate' => 'Kurumsal'] as $value => $label)
          <option value="{{ $value }}" @selected(old('category', $document->category ?? 'legal') === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group col-md-3">
      <label>SEO Başlık</label>
      <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $document->meta_title ?? '') }}">
    </div>
    <div class="form-group col-12">
      <label>SEO Açıklama</label>
      <textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description', $document->meta_description ?? '') }}</textarea>
    </div>
    <div class="form-group col-12">
      <label>İçerik</label>
      <textarea name="content" cols="30" rows="18" class="summernote">{{ old('content', $document->content ?? '') }}</textarea>
    </div>
    <div class="form-group col-md-4">
      <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="is_published" name="is_published" value="1" @checked(old('is_published', $document->is_published ?? false))>
        <label class="custom-control-label" for="is_published">Yayında mı?</label>
      </div>
    </div>
    <div class="form-group col-md-4">
      <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="requires_consent" name="requires_consent" value="1" @checked(old('requires_consent', $document->requires_consent ?? false))>
        <label class="custom-control-label" for="requires_consent">Zorunlu Onay mı?</label>
      </div>
    </div>
    <div class="form-group col-md-4">
      <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $document->is_active ?? true))>
        <label class="custom-control-label" for="is_active">Aktif</label>
      </div>
    </div>
    <div class="col-12">
      <button class="btn btn-primary">{{ $document ? __('admin.Update') : __('admin.Save') }}</button>
      <a href="{{ route('admin.legal-documents.index') }}" class="btn btn-secondary">Geri</a>
    </div>
  </div>
</form>
