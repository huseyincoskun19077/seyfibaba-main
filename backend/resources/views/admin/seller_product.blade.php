@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Products')}}</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Products')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Products')}}</div>
            </div>
          </div>

          <div class="section-body">
            <a href="{{ route('admin.product.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{__('admin.Add New')}}</a>
            <div class="card mt-4 mb-3">
              <div class="card-body">
                <form method="GET" action="{{ route('admin.seller-product') }}" class="row align-items-end">
                  <div class="col-md-4">
                    <label class="mb-1">Ürün / satıcı ara</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Ürün adı, slug, SKU veya mağaza">
                  </div>
                  <div class="col-md-3">
                    <label class="mb-1">Kategori</label>
                    <select name="category_id" class="form-control">
                      <option value="all" {{ request('category_id', 'all') === 'all' ? 'selected' : '' }}>Tümü</option>
                      @foreach(($categories ?? []) as $category)
                        <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="mb-1">Durum</label>
                    <select name="status" class="form-control">
                      <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>Tümü</option>
                      <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                      <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pasif</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">Filtrele</button>
                    @if(request()->hasAny(['search', 'category_id', 'status']))
                      <a href="{{ route('admin.seller-product') }}" class="btn btn-light btn-block mt-1">Temizle</a>
                    @endif
                  </div>
                </form>
              </div>
            </div>
            <div class="row">
                <div class="col">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted">
                          @if($products->total() > 0)
                            {{ $products->firstItem() }}–{{ $products->lastItem() }} / {{ $products->total() }} ürün
                          @else
                            Sonuç bulunamadı.
                          @endif
                        </div>
                      </div>
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="8%">Fotoğraf</th>
                                    <th width="12%">{{__('admin.Seller')}}</th>
                                    <th width="20%">{{__('admin.Product')}}</th>
                                    <th width="8%">{{__('admin.Price')}}</th>
                                    <th width="12%">Kategori</th>
                                    <th width="12%">Alt Kategori</th>
                                    <th width="10%">{{__('admin.Status')}}</th>
                                    <th width="15%">{{__('admin.Action')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr>
                                        <td>{{ $products->firstItem() + $loop->index }}</td>
                                        <td>
                                            @if($product->thumb_image)
                                                <img src="{{ asset($product->thumb_image) }}" alt="" width="60" height="60" style="object-fit:cover;border-radius:4px;">
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->seller && $product->seller->user)
                                                <a href="{{ route('admin.seller-show', $product->vendor_id) }}">{{ $product->seller->shop_name ?? $product->seller->user->name }}</a>
                                            @else
                                                <span class="text-muted">ID:{{ $product->vendor_id }}</span>
                                            @endif
                                        </td>
                                        <td><a href="{{ $frontend_url }}{{ $product->slug ?? '' }}" target="_blank" rel="noopener">{{ $product->short_name ?? '—' }}</a></td>
                                        <td>{{ ($setting->currency_icon ?? '₺') }}{{ $product->price ?? 0 }}</td>
                                        <td>
                                            @if($product->category)
                                                <span class="badge badge-light">{{ $product->category->name }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->subCategory)
                                                <span class="badge badge-info">{{ $product->subCategory->name }}</span>
                                            @else
                                                <span class="badge badge-danger">Atanmamış</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->status == 1)
                                            <a href="javascript:;" onclick="changeProductStatus({{ $product->id }})">
                                                <input id="status_toggle" type="checkbox" checked data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.InActive')}}" data-onstyle="success" data-offstyle="danger">
                                            </a>

                                            @else
                                            <a href="javascript:;" onclick="changeProductStatus({{ $product->id }})">
                                                <input id="status_toggle" type="checkbox" data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.InActive')}}" data-onstyle="success" data-offstyle="danger">
                                            </a>

                                            @endif
                                        </td>
                                        <td>
                                        <a href="{{ route('admin.product.edit',$product->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-edit" aria-hidden="true"></i></a>

                                        @php
                                            $existOrder = $orderProducts->where('product_id',$product->id)->count();
                                        @endphp

                                        @if ($existOrder == 0)
                                            <a href="javascript:;" data-toggle="modal" data-target="#deleteModal" class="btn btn-danger btn-sm" onclick="deleteData({{ $product->id }})"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        @else
                                            <a href="javascript:;" data-toggle="modal" data-target="#canNotDeleteModal" class="btn btn-danger btn-sm" disabled><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        @endif

                                        <div class="dropdown d-inline">
                                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                              <i class="fas fa-cog"></i>
                                            </button>

                                            <div class="dropdown-menu" x-placement="top-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, -131px, 0px);">
                                              <a class="dropdown-item has-icon" href="{{ route('admin.product-gallery',$product->id) }}"><i class="far fa-image"></i> {{__('admin.Image Gallery')}}</a>

                                              <a class="dropdown-item has-icon" href="{{ route('admin.product-variant',$product->id) }}"><i class="fas fa-cog"></i>{{__('admin.Product Variant')}}</a>

                                            </div>
                                          </div>

                                        </td>
                                    </tr>
                                  @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">Filtrelere uygun ürün yok.</td>
                                    </tr>
                                  @endforelse
                            </tbody>
                        </table>
                        @if($products->hasPages())
                          <div class="mt-3 seller-product-pagination">
                            {{ $products->onEachSide(1)->links('pagination::bootstrap-4') }}
                          </div>
                        @endif
                      </div>
                    </div>
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
                          {{__('admin.You can not delete this product. Because there are one or more order has been created in this product.')}}
                      </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">{{__('admin.Close')}}</button>
                </div>
            </div>
        </div>
    </div>

<script>
    function deleteData(id){
        $("#deleteForm").attr("action",'{{ url("admin/product/") }}'+"/"+id)
    }
    function changeProductStatus(id){
        var isDemo = "{{ env('APP_VERSION') }}"
        if(isDemo == 0){
            toastr.error('Bu demo sürümdür. Herhangi bir değişiklik yapamazsınız.');
            return;
        }
        $.ajax({
            type:"put",
            data: { _token : '{{ csrf_token() }}' },
            url:"{{url('/admin/product-status/')}}"+"/"+id,
            success:function(response){
                toastr.success(response)
            },
            error:function(err){
                console.log(err);

            }
        })
    }
</script>
<style>
  .seller-product-pagination svg,
  .seller-product-pagination img,
  .seller-product-pagination .page-link i,
  .seller-product-pagination [aria-hidden="true"] svg {
    display: none !important;
  }
  .seller-product-pagination nav > div.flex {
    display: none !important;
  }
</style>
@endsection
