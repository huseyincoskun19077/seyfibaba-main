@extends('admin.master_layout')
@section('title')
<title>Öneri Ayarları</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Öneri ve gezinme geçmişi ayarları</h1>
    </div>
    <div class="section-body">
      <div class="alert alert-info">
        Ağırlıklar öneri skoruna katkı payıdır. Gezinme geçmişi yalnızca izinli kullanıcı/misafir için kullanılır.
        Görüntülenen ürün “Size Özel”de tekrar gösterilmez; ilgili alternatifler önerilir.
      </div>
      <div class="card">
        <div class="card-body">
          <form method="POST" action="{{ route('admin.recommendation-settings.update') }}">
            @csrf
            @method('PUT')
            <div class="form-row">
              <div class="form-group col-md-3">
                <label>Alan ağırlığı</label>
                <input type="number" name="weight_segment" class="form-control" value="{{ $settings->weight_segment }}" min="0" max="100">
              </div>
              <div class="form-group col-md-3">
                <label>Gezinme geçmişi ağırlığı</label>
                <input type="number" name="weight_browse_history" class="form-control" value="{{ $settings->weight_browse_history }}" min="0" max="100">
              </div>
              <div class="form-group col-md-3">
                <label>İşletme türü ağırlığı</label>
                <input type="number" name="weight_business_type" class="form-control" value="{{ $settings->weight_business_type }}" min="0" max="100">
              </div>
              <div class="form-group col-md-3">
                <label>Popülerlik ağırlığı</label>
                <input type="number" name="weight_popularity" class="form-control" value="{{ $settings->weight_popularity }}" min="0" max="100">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-3">
                <label>Geçmiş süresi (gün)</label>
                <input type="number" name="history_days" class="form-control" value="{{ $settings->history_days }}" min="1" max="365">
              </div>
              <div class="form-group col-md-3">
                <label>Sinyal için min. görüntüleme</label>
                <input type="number" name="min_views_for_signal" class="form-control" value="{{ $settings->min_views_for_signal }}" min="1" max="20">
                <small class="text-muted">Tek yanlış tıklama öneriyi değiştirmez.</small>
              </div>
              <div class="form-group col-md-3">
                <label>Satıcı çeşitliliği (öneri)</label>
                <input type="number" name="vendor_diversity" class="form-control" value="{{ $settings->vendor_diversity }}" min="1" max="20">
              </div>
              <div class="form-group col-md-3">
                <label class="d-block">&nbsp;</label>
                <label><input type="checkbox" name="exclude_viewed_product" value="1" {{ $settings->exclude_viewed_product ? 'checked' : '' }}> Görüntülenen ürünü öneriden çıkar</label>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Kaydet</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
