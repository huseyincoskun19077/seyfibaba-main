@extends('admin.master_layout')
@section('title')
<title>Taksit Kategori Ayarları</title>
@endsection

@section('admin-content')
<div class="main-content">
  <section class="section">
    <div class="section-header">
      <h1>Taksit Kategori Ayarları</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
        <div class="breadcrumb-item">Taksit Kategorileri</div>
      </div>
    </div>

    <div class="section-body">
      @if(!$hasColumns)
        <div class="alert alert-danger">
          <strong>DB güncel değil:</strong> `max_installment` kolonları bulunamadı.
          <div class="mt-2">
            Backend klasöründe şu komutu çalıştırın:
            <code>php artisan migrate</code>
          </div>
        </div>
      @else
        <div class="alert alert-info">
          <strong>Iyzico onay tablosu → Seyfibaba eşlemesi</strong>
          <p class="mb-2 mt-2">Ödeme sırasında Iyzico'ya hem <code>enabledInstallments</code> hem sepetteki ürün kategorisi gönderilir. Sepette en düşük taksit limiti tüm siparişe uygulanır.</p>
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 bg-white">
              <thead>
                <tr>
                  <th>Iyzico Kategorisi</th>
                  <th>Max Taksit</th>
                  <th>Seyfibaba'da nereye yazılır?</th>
                </tr>
              </thead>
              <tbody>
                @foreach($iyzicoRules as $rule)
                  <tr>
                    <td>{{ $rule['label'] }}</td>
                    <td><strong>{{ $rule['max_installment'] <= 1 ? 'Tek çekim (1)' : $rule['max_installment'] }}</strong></td>
                    <td>{{ $rule['site_hint'] }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <p class="mb-0 mt-2 small text-muted">Berber/kuaför sitesi için çoğu kategori <strong>9</strong>, kozmetik <strong>1</strong> olmalıdır. Tablet/telefon satmıyorsanız ilgili satırları yok sayabilirsiniz.</p>
        </div>
      @endif

      <form method="POST" action="{{ route('admin.installment-categories.update') }}">
        @csrf
        @method('PUT')

        <div class="card">
          <div class="card-header">
            <h4>Ana Kategoriler (Category)</h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Kategori</th>
                    <th style="width:220px;">Max Taksit (0=Tek Çekim)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($categories as $cat)
                    <tr>
                      <td>{{ $cat->name }}</td>
                      <td>
                        <input
                          type="number"
                          min="0"
                          max="12"
                          class="form-control"
                          name="categories[{{ $cat->id }}]"
                          value="{{ old('categories.'.$cat->id, $hasColumns ? $cat->max_installment : '') }}"
                          placeholder="Boş = default"
                          @if(!$hasColumns) disabled @endif
                        >
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card mt-4">
          <div class="card-header">
            <h4>Alt Kategoriler (SubCategory)</h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Alt Kategori</th>
                    <th style="width:220px;">Max Taksit (0=Tek Çekim)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($subCategories as $sub)
                    <tr>
                      <td>{{ $sub->name }}</td>
                      <td>
                        <input
                          type="number"
                          min="0"
                          max="12"
                          class="form-control"
                          name="sub_categories[{{ $sub->id }}]"
                          value="{{ old('sub_categories.'.$sub->id, $hasColumns ? $sub->max_installment : '') }}"
                          placeholder="Boş = ana kategori"
                          @if(!$hasColumns) disabled @endif
                        >
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="mt-3">
          <button class="btn btn-primary" type="submit" @if(!$hasColumns) disabled @endif>Kaydet</button>
        </div>
      </form>
    </div>
  </section>
</div>
@endsection

