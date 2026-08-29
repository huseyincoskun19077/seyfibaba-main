@extends('seller.master_layout')
@section('title')
<title>{{__('admin.Products')}}</title>
@endsection
@section('seller-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Products')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Products')}}</div>
            </div>
          </div>

          <div class="section-body">
            <a href="{{ route('seller.product.quick-create') }}" class="btn btn-primary" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;"><i class="fas fa-bolt"></i> Hızlı Ürün Ekle</a>
            <a href="{{ route('seller.product-import-page') }}" class="btn btn-success"><i class="fas fa-file-excel"></i> Toplu Excel Yükle</a>
            <a href="{{ route('seller.product.create') }}" class="btn btn-outline-primary"><i class="fas fa-plus"></i> {{__('admin.Add New')}}</a>
            <div class="row mt-4">
                <div class="col">
                  <div class="card">
                    <div class="card-body">
                      <form method="GET" action="{{ route('seller.product.index') }}" class="mb-3">
                        <div class="form-row align-items-end" style="gap:8px 0;">
                          <div class="form-group col-12 col-md-5 mb-2">
                            <label>Ara</label>
                            <input type="text" name="q" class="form-control" value="{{ $q ?? '' }}" placeholder="Ürün adı, SKU veya slug">
                          </div>
                          <div class="form-group col-12 col-md-3 mb-2">
                            <label>Durum</label>
                            <select name="filter" class="form-control">
                              <option value="all" {{ ($filter ?? 'all') === 'all' ? 'selected' : '' }}>Tümü</option>
                              <option value="active" {{ ($filter ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                              <option value="inactive" {{ ($filter ?? '') === 'inactive' ? 'selected' : '' }}>Pasif</option>
                              <option value="low" {{ ($filter ?? '') === 'low' ? 'selected' : '' }}>Düşük stok</option>
                              <option value="out" {{ ($filter ?? '') === 'out' ? 'selected' : '' }}>Tükendi</option>
                            </select>
                          </div>
                          <div class="form-group col-12 col-md-4 mb-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrele</button>
                            @if (($q ?? '') !== '' || ($filter ?? 'all') !== 'all')
                              <a href="{{ route('seller.product.index') }}" class="btn btn-outline-secondary">Temizle</a>
                            @endif
                          </div>
                        </div>
                      </form>
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped" id="sellerProductTable">
                            <thead>
                                <tr>
                                    <th width="5%">{{__('admin.SN')}}</th>
                                    <th width="25%">{{__('admin.Name')}}</th>
                                    <th width="8%">{{__('admin.Price')}}</th>
                                    <th width="8%">İndirimli</th>
                                    <th width="12%">{{__('admin.Photo')}}</th>
                                    <th width="12%">Kategori</th>
                                    <th width="12%">Alt Kategori</th>
                                    <th width="10%">{{__('admin.Status')}}</th>
                                    <th width="20%">{{__('admin.Action')}}</th>
                                  </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $index => $product)
                                    <tr>
                                        <td>{{ $products->firstItem() + $index }}</td>
                                        <td>
                                          @php
                                            $storefront = rtrim((string) ($setting?->frontend_url ?? config('app.frontend_url', 'https://seyfibaba.com')), '/');
                                            $publicUrl = $product->slug ? $storefront.'/urun/'.$product->slug : null;
                                          @endphp
                                          @if ($publicUrl)
                                            <a href="{{ $publicUrl }}" target="_blank">{{ $product->short_name ?: $product->name }}</a>
                                          @else
                                            {{ $product->short_name ?: $product->name }}
                                          @endif
                                        </td>
                                        <td>{{ $setting->currency_icon }}{{ $product->price }}</td>
                                        <td>
                                            @if ($product->offer_price > 0)
                                                <span class="text-success font-weight-bold">{{ $setting->currency_icon }}{{ $product->offer_price }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td> <img class="rounded-circle" src="{{ product_image_url($product->thumb_image) }}" alt="" width="80px"></td>
                                        <td>
                                            @if ($product->category)
                                                <span class="d-block">{{ $product->category->name }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($product->subCategory)
                                                <span class="d-block">{{ $product->subCategory->name }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @include('seller.partials.product_status_badges', ['product' => $product])
                                        </td>
                                        <td>
                                        <a href="{{ route('seller.product.edit',$product->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                        <form action="{{ route('seller.product.duplicate', $product->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm" title="Kopyala">
                                                <i class="fa fa-copy" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                        @php
                                            $existOrder = $orderProducts->where('product_id',$product->id)->count();
                                        @endphp
                                        @if ($existOrder == 0)
                                            <a href="javascript:;" data-toggle="modal" data-target="#deleteModal" class="btn btn-danger btn-sm" onclick="deleteData({{ $product->id }})"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        @else
                                            <a href="javascript:;" data-toggle="modal" data-target="#canNotDeleteModal" class="btn btn-danger btn-sm" onclick="setDeactivateProductId({{ $product->id }})"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        @endif

                                        {{-- Resim ve varyant butonları — ikon yerine yazı (#31) --}}
                                        <a class="btn btn-info btn-sm" href="{{ route('seller.product.edit',$product->id) }}#tab-images" onclick="localStorage.setItem('sellerProductTab','images')">
                                            <i class="far fa-image"></i> Resim Ekle
                                        </a>
                                        <a class="btn btn-warning btn-sm" href="{{ route('seller.product-variant',$product->id) }}">
                                            <i class="fas fa-layer-group"></i> Varyant Ekle
                                        </a>

                                        </td>
                                    </tr>
                                  @empty
                                    <tr>
                                      <td colspan="9" class="text-center text-muted py-4">
                                        {{ ($q ?? '') !== '' || ($filter ?? 'all') !== 'all' ? 'Filtreye uyan ürün yok.' : 'Henüz ürün yok.' }}
                                      </td>
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

      <!-- Modal -->
      <div class="modal fade" id="canNotDeleteModal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                      <div class="modal-body">
                          Bu ürün daha önce satıldığı için silinemez. Satış geçmişi korunur; ürünü yayından kaldırmak için pasife alabilirsiniz.
                      </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('admin.Close')}}</button>
                    <button type="button" class="btn btn-warning" onclick="deactivateProductFromModal()">Pasife Al</button>
                </div>
            </div>
        </div>
    </div>

<script>
    var deactivateProductId = null;

    function deleteData(id){
        $("#deleteForm").attr("action",'{{ url("seller/product/") }}'+"/"+id)
    }

    function setDeactivateProductId(id) {
        deactivateProductId = id;
    }

    function changeProductStatus(id){
        $.ajax({
            type:"put",
            data: { _token : '{{ csrf_token() }}' },
            url:"{{ url('/seller/product-status/') }}/"+id,
            success:function(response){
                toastr.success(response);
                setTimeout(function(){ location.reload(); }, 600);
            },
            error:function(xhr){
                var message = xhr.responseJSON;
                if (typeof message === 'string') {
                    toastr.error(message);
                } else {
                    toastr.error('Durum güncellenemedi.');
                }
                setTimeout(function(){ location.reload(); }, 600);
            }
        });
    }

    function deactivateProductFromModal() {
        if (!deactivateProductId) {
            return;
        }
        $('#canNotDeleteModal').modal('hide');
        $.ajax({
            type:"put",
            data: { _token : '{{ csrf_token() }}', deactivate: 1 },
            url:"{{ url('/seller/product-status/') }}/"+deactivateProductId,
            success:function(response){
                toastr.success(response);
                setTimeout(function(){ location.reload(); }, 600);
            },
            error:function(xhr){
                var message = xhr.responseJSON;
                toastr.error(typeof message === 'string' ? message : 'Durum güncellenemedi.');
            }
        });
    }
</script>
@endsection
