@extends('seller.master_layout')
@section('title')
<title>{{__('admin.Stock out products')}}</title>
@endsection
@section('seller-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Stock out products')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Stock out products')}}</div>
            </div>
          </div>

          <div class="section-body">
            <a href="{{ route('seller.product.index', ['filter' => 'out']) }}" class="btn btn-outline-primary btn-sm mb-3">Ürün listesinde de filtrele</a>
            <div class="row mt-2">
                <div class="col">
                  <div class="card">
                    <div class="card-header">
                      <h4 class="mb-0">Stok adedi 0 veya daha az olan ürünler</h4>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">{{__('admin.SN')}}</th>
                                    <th width="28%">{{__('admin.Name')}}</th>
                                    <th width="10%">{{__('admin.Price')}}</th>
                                    <th width="10%">İndirimli</th>
                                    <th width="12%">{{__('admin.Photo')}}</th>
                                    <th width="12%">Kategori</th>
                                    <th width="8%">Stok</th>
                                    <th width="15%">{{__('admin.Action')}}</th>
                                  </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $index => $product)
                                    <tr>
                                        <td>{{ $products->firstItem() + $index }}</td>
                                        <td>{{ $product->short_name ?: $product->name }}</td>
                                        <td>{{ $setting->currency_icon }}{{ $product->price }}</td>
                                        <td>
                                            @if ($product->offer_price > 0)
                                                <span class="text-success font-weight-bold">{{ $setting->currency_icon }}{{ $product->offer_price }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td><img class="rounded-circle" src="{{ product_image_url($product->thumb_image) }}" alt="" width="80px"></td>
                                        <td>
                                            @if ($product->category)
                                                <span class="d-block">{{ $product->category->name }}</span>
                                            @endif
                                            @if ($product->subCategory)
                                                <small class="text-muted">{{ $product->subCategory->name }}</small>
                                            @endif
                                            @if ($product->childCategory)
                                                <small class="d-block text-muted">{{ $product->childCategory->name }}</small>
                                            @endif
                                        </td>
                                        <td><span class="badge badge-danger">{{ (int) $product->qty }}</span></td>
                                        <td>
                                            <a href="{{ route('seller.product.edit',$product->id) }}" class="btn btn-primary btn-sm" title="Düzenle / Stok güncelle"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                            <a href="{{ route('seller.inventory') }}" class="btn btn-warning btn-sm" title="Envanter"><i class="fas fa-boxes"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Stokta olmayan ürün yok.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                      </div>
                      @if ($products->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                          {{ $products->onEachSide(1)->links('pagination::bootstrap-4') }}
                        </div>
                      @endif
                      <div class="text-muted small mt-2">Toplam {{ $products->total() }} ürün</div>
                    </div>
                  </div>
                </div>
          </div>
        </section>
      </div>

@endsection
