@extends('admin.master_layout')
@section('title')
<title>Push Bildirimleri</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Push Bildirimleri</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">Push Bildirimleri</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header"><h4>Özel / Kampanya / Duyuru Gönder</h4></div>
        <div class="card-body">
          <form action="{{ route('admin.push-notifications.store') }}" method="POST">
            @csrf
            <div class="form-group">
              <label>Başlık</label>
              <input type="text" name="title" class="form-control" value="{{ old('title') }}" maxlength="120" required>
            </div>
            <div class="form-group">
              <label>Mesaj</label>
              <textarea name="message" class="form-control" rows="4" maxlength="500" required>{{ old('message') }}</textarea>
            </div>
            <div class="form-group">
              <label>Hedef Kitle</label>
              <select name="target" class="form-control" id="push-target">
                <option value="all_buyers" {{ old('target') === 'all_sellers' ? '' : 'selected' }}>Tüm alıcılar</option>
                <option value="all_sellers" {{ old('target') === 'all_sellers' ? 'selected' : '' }}>Tüm satıcılar</option>
                <option value="single_user" {{ old('target') === 'single_user' ? 'selected' : '' }}>Tek kullanıcı (e-posta)</option>
              </select>
            </div>
            <div class="form-group" id="user-email-group">
              <label>Kullanıcı E-postası</label>
              <input type="email" name="user_email" class="form-control" value="{{ old('user_email') }}">
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label>Ürün Slug (opsiyonel deep-link)</label>
                <input type="text" name="product_slug" class="form-control" value="{{ old('product_slug') }}">
              </div>
              <div class="form-group col-md-6">
                <label>Kampanya Slug/ID (opsiyonel deep-link)</label>
                <input type="text" name="campaign_slug" class="form-control" value="{{ old('campaign_slug') }}">
              </div>
            </div>
            <button class="btn btn-primary" type="submit">Bildirimi Gönder</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
<script>
  (function () {
    var target = document.getElementById('push-target');
    var emailGroup = document.getElementById('user-email-group');
    function syncEmailField() {
      emailGroup.style.display = target.value === 'single_user' ? 'block' : 'none';
    }
    target.addEventListener('change', syncEmailField);
    syncEmailField();
  })();
</script>
@endsection
