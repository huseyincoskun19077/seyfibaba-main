@extends('admin.master_layout')
@section('title')
<title>Stok Uyarıları</title>
@endsection
@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Stok Uyarıları</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">Stok Uyarıları</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-md-4">
          <div class="card card-statistic-1">
            <div class="card-icon bg-danger">
              <i class="fas fa-box-open"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header"><h4>Düşük Stoklu Ürünler</h4></div>
              <div class="card-body">{{ $products->count() }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning">
              <i class="fas fa-sliders-h"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header"><h4>Göreli Stok Eşiği</h4></div>
              <div class="card-body">%{{ $setting->low_stock_relative_percent ?? 20 }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-statistic-1">
            <div class="card-icon {{ ($setting->stock_alert_enabled ?? true) ? 'bg-success' : 'bg-secondary' }}">
              <i class="fas fa-bell"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header"><h4>Uyarı Durumu</h4></div>
              <div class="card-body">{{ ($setting->stock_alert_enabled ?? true) ? 'Açık' : 'Kapalı' }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h4>Uyarı Ayarları</h4>
        </div>
        <div class="card-body">
          <form action="{{ route('admin.stock-alerts.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
              <div class="form-group col-md-6">
                <label>Göreli Stok Yüzdesi (%)</label>
                <input type="number" min="1" max="100" name="low_stock_relative_percent" class="form-control" value="{{ old('low_stock_relative_percent', $setting->low_stock_relative_percent ?? 20) }}" required>
                <small class="text-muted">Örn: 10 stoklu üründe %20 = 2 adet kaldığında bildirim.</small>
              </div>
              <div class="form-group col-md-6">
                <label>Minimum Stok Adedi</label>
                <input type="number" min="1" max="1000" name="low_stock_min_qty" class="form-control" value="{{ old('low_stock_min_qty', $setting->low_stock_min_qty ?? 1) }}" required>
              </div>
              <div class="form-group col-md-6">
                <label>Stok Uyarıları</label>
                <select name="stock_alert_enabled" class="form-control">
                  <option value="1" {{ (int) old('stock_alert_enabled', $setting->stock_alert_enabled ?? 1) === 1 ? 'selected' : '' }}>Aç</option>
                  <option value="0" {{ (int) old('stock_alert_enabled', $setting->stock_alert_enabled ?? 1) === 0 ? 'selected' : '' }}>Kapat</option>
                </select>
              </div>
              <div class="form-group col-md-6">
                <label>Ürün Bakış Hatırlatma (kez)</label>
                <input type="number" min="1" max="20" name="product_view_reminder_count" class="form-control" value="{{ old('product_view_reminder_count', $setting->product_view_reminder_count ?? 3) }}" required>
              </div>
              <div class="form-group col-md-6">
                <label>Hatırlatma Tekrar Aralığı (gün)</label>
                <input type="number" min="1" max="90" name="product_view_reminder_cooldown_days" class="form-control" value="{{ old('product_view_reminder_cooldown_days', $setting->product_view_reminder_cooldown_days ?? 7) }}" required>
              </div>
            </div>
            <button class="btn btn-primary" type="submit">Değişiklikleri Kaydet</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h4>Göreli Eşik Altındaki Ürünler</h4>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped" id="dataTable">
              <thead>
                <tr>
                  <th>SN</th>
                  <th>Ürün Adı</th>
                  <th>Satıcı</th>
                  <th>Başlangıç</th>
                  <th>Kalan</th>
                  <th>İşlem</th>
                </tr>
              </thead>
              <tbody>
                @foreach($products as $index => $product)
                  <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ optional($product->seller)->shop_name ?: 'Yönetici Ürünü' }}</td>
                    <td>{{ $product->initial_qty ?? $product->qty }}</td>
                    <td><span class="badge badge-danger">{{ $product->qty }}</span></td>
                    <td>
                      <a href="{{ route('admin.product.edit', $product->id) }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-edit"></i>
                      </a>
                      <a href="{{ route('admin.stock-history', $product->id) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-history"></i>
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          @if($products->isEmpty())
            <div class="alert alert-info mb-0">Şu anda göreli stok eşiğinde ürün bulunmuyor.</div>
          @endif
        </div>
      </div>
    </div>
  </section>
</div>
@endsection
