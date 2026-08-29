@extends('admin.master_layout')
@section('title')
<title>İkinci El Sözleşmeler</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>İkinci El Sözleşmeler</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.second-hand.index') }}">İkinci El</a></div>
        <div class="breadcrumb-item">Sözleşmeler</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header">
          <h4>Sözleşme Metinleri</h4>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.second-hand.agreements.update') }}">
            @csrf
            @method('PUT')

            <div class="form-group">
              <label>İkinci El Sözleşme Başlığı</label>
              <input
                type="text"
                name="terms_title"
                class="form-control"
                value="{{ old('terms_title', $agreement->terms_title) }}"
                required
              >
            </div>

            <div class="form-group">
              <label>İkinci El Sözleşme Metni</label>
              <textarea
                name="terms_content"
                rows="12"
                class="form-control"
                required
              >{{ old('terms_content', $agreement->terms_content) }}</textarea>
              <small class="text-muted">Maddeleri satır satır yazabilirsiniz.</small>
            </div>

            <hr>

            <div class="form-group">
              <label>İkinci El KVKK / Gizlilik Başlığı</label>
              <input
                type="text"
                name="privacy_title"
                class="form-control"
                value="{{ old('privacy_title', $agreement->privacy_title) }}"
                required
              >
            </div>

            <div class="form-group">
              <label>İkinci El KVKK / Gizlilik Metni</label>
              <textarea
                name="privacy_content"
                rows="12"
                class="form-control"
                required
              >{{ old('privacy_content', $agreement->privacy_content) }}</textarea>
              <small class="text-muted">Bu metin ikinci el doğrulama onayında ayrıca gösterilir.</small>
            </div>

            <button type="submit" class="btn btn-primary">Kaydet</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

