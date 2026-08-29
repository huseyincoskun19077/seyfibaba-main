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

            <!-- Product Statistics -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Günlük Tıklamalar</p>
                                <h3 class="mb-3 font-weight-bold">{{ $productViews->sum("daily_views") }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Product Statistics -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Günlük Tıklamalar</p>
                                <h3 class="mb-3 font-weight-bold">{{ $productViews->sum("daily_views") }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Aylık Tıklamalar</p>
                                <h3 class="mb-3 font-weight-bold">{{ $productViews->sum("monthly_views") }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Yıllık Tıklamalar</p>
                                <h3 class="mb-3 font-weight-bold">{{ $productViews->sum("yearly_views") }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Sepete Eklemeler</p>
                                <h3 class="mb-3 font-weight-bold">{{ $productViews->sum("add_to_cart_count") }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-2 mb-4">
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Sepetteki Ürünler</p>
                                <h3 class="mb-3 font-weight-bold">{{ $shoppingCarts->count() }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Toplam Satın Alma</p>
                                <h3 class="mb-3 font-weight-bold">{{ $productViews->sum("purchase_count") }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted">Toplam Görüntüleme</p>
                                <h3 class="mb-3 font-weight-bold">{{ $productViews->sum("view_count") }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-2 mb-4">
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted mb-1">En popüler ürün → En popüler ürünler</p>
                                <h5 class="mb-2 font-weight-bold" id="flag-count-is_top">
                                    Seçilen: {{ $homepageFlagCounts['is_top']['selected'] }}
                                    · Anasayfada: {{ $homepageFlagCounts['is_top']['homepage'] }}
                                </h5>
                                <div class="d-flex" style="gap:6px;">
                                    <input type="number" min="1" max="24" class="form-control form-control-sm homepage-qty" data-flag="is_top" value="{{ $homepageFlagCounts['is_top']['qty'] }}">
                                    <button type="button" class="btn btn-sm btn-primary save-homepage-qty" data-flag="is_top">Kaydet</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted mb-1">Öne çıkan ürün → Öne çıkan ürünler</p>
                                <h5 class="mb-2 font-weight-bold" id="flag-count-is_featured">
                                    Seçilen: {{ $homepageFlagCounts['is_featured']['selected'] }}
                                    · Anasayfada: {{ $homepageFlagCounts['is_featured']['homepage'] }}
                                </h5>
                                <div class="d-flex" style="gap:6px;">
                                    <input type="number" min="1" max="24" class="form-control form-control-sm homepage-qty" data-flag="is_featured" value="{{ $homepageFlagCounts['is_featured']['qty'] }}">
                                    <button type="button" class="btn btn-sm btn-primary save-homepage-qty" data-flag="is_featured">Kaydet</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted mb-1">En çok satan → En iyi ürünler</p>
                                <h5 class="mb-2 font-weight-bold" id="flag-count-is_best">
                                    Seçilen: {{ $homepageFlagCounts['is_best']['selected'] }}
                                    · Anasayfada: {{ $homepageFlagCounts['is_best']['homepage'] }}
                                </h5>
                                <div class="d-flex" style="gap:6px;">
                                    <input type="number" min="1" max="24" class="form-control form-control-sm homepage-qty" data-flag="is_best" value="{{ $homepageFlagCounts['is_best']['qty'] }}">
                                    <button type="button" class="btn btn-sm btn-primary save-homepage-qty" data-flag="is_best">Kaydet</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-statistic">
                        <div class="card-body">
                            <div class="wrapper">
                                <p class="card-text text-muted mb-1">Yeni gelenler → Yeni gelen ürünler</p>
                                <h5 class="mb-2 font-weight-bold" id="flag-count-new_product">
                                    Seçilen: {{ $homepageFlagCounts['new_product']['selected'] }}
                                    · Anasayfada: {{ $homepageFlagCounts['new_product']['homepage'] }}
                                </h5>
                                <div class="d-flex" style="gap:6px;">
                                    <input type="number" min="1" max="24" class="form-control form-control-sm homepage-qty" data-flag="new_product" value="{{ $homepageFlagCounts['new_product']['qty'] }}">
                                    <button type="button" class="btn btn-sm btn-primary save-homepage-qty" data-flag="new_product">Kaydet</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-muted small mb-3">Web ve mobil aynı adedi kullanır. Seçilen ürün bu adetten azsa kalan yerler rastgele dolar. Liste varsayılan olarak anasayfa vitrinindeki (işaretli) ürünleri gösterir.</p>
            <a href="{{ route('admin.product.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{__('admin.Add New')}}</a>
            <form method="GET" action="{{ route('admin.product.index') }}" class="form-inline mt-3 mb-2" style="gap:8px;">
                <input type="hidden" name="vitrine" value="{{ $vitrine }}">
                <input type="text" name="search" class="form-control" value="{{ $search ?? request('search') }}" placeholder="Ürün ara">
                <button class="btn btn-outline-primary" type="submit">Ara</button>
            </form>
            <div class="mb-3">
                <a class="btn btn-sm {{ ($vitrine ?? 'homepage') === 'homepage' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.product.index', ['vitrine' => 'homepage']) }}">Anasayfa ürünleri</a>
                <a class="btn btn-sm {{ ($vitrine ?? '') === 'all' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.product.index', ['vitrine' => 'all']) }}">Tüm onaylı ürünler</a>
                <a class="btn btn-sm {{ ($vitrine ?? '') === 'is_top' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.product.index', ['vitrine' => 'is_top']) }}">En popüler</a>
                <a class="btn btn-sm {{ ($vitrine ?? '') === 'is_featured' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.product.index', ['vitrine' => 'is_featured']) }}">Öne çıkan</a>
                <a class="btn btn-sm {{ ($vitrine ?? '') === 'is_best' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.product.index', ['vitrine' => 'is_best']) }}">En çok satan</a>
                <a class="btn btn-sm {{ ($vitrine ?? '') === 'new_product' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.product.index', ['vitrine' => 'new_product']) }}">Yeni gelenler</a>
            </div>
            @if(($vitrine ?? 'homepage') === 'homepage' && $products->total() === 0)
                <div class="alert alert-warning">Henüz vitrine işaretlenmiş ürün yok. Yeni ürün işaretlemek için <a href="{{ route('admin.product.index', ['vitrine' => 'all']) }}">Tüm onaylı ürünler</a>e gidin.</div>
            @endif
            <div class="row mt-4">
                <div class="col">
                  <div class="card">
                    <div class="card-body">
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">{{__('admin.SN')}}</th>
                                    <th width="28%">{{__('admin.Name')}}</th>
                                    <th width="10%">{{__('admin.Price')}}</th>
                                    <th width="12%">{{__('admin.Photo')}}</th>
                                    <th width="22%">Anasayfa</th>
                                    <th width="10%">{{__('admin.Status')}}</th>
                                    <th width="13%">{{__('admin.Action')}}</th>
                                  </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $index => $product)
                                    <tr>
                                        <td>{{ $products->firstItem() + $index }}</td>
                                        <td>
                                            <a target="_blank" href="{{ $frontend_url.$product->slug }}">{{ $product->short_name }}</a>
                                            <div class="text-muted small">{{ optional($product->seller)->shop_name ?: 'Satıcı ürünü' }}</div>
                                        </td>
                                        <td>{{ $setting->currency_icon }}{{ $product->price }}</td>
                                        <td> <img class="rounded-circle" src="{{ asset($product->thumb_image) }}" alt="" width="100px" height="100px"></td>
                                        <td class="small">
                                            <label class="d-block mb-1">
                                                <input type="checkbox" class="homepage-flag" data-id="{{ $product->id }}" data-flag="is_top" {{ (int) $product->is_top === 1 ? 'checked' : '' }}>
                                                En popüler ürün
                                            </label>
                                            <label class="d-block mb-1">
                                                <input type="checkbox" class="homepage-flag" data-id="{{ $product->id }}" data-flag="is_featured" {{ (int) $product->is_featured === 1 ? 'checked' : '' }}>
                                                Öne çıkan ürün
                                            </label>
                                            <label class="d-block mb-1">
                                                <input type="checkbox" class="homepage-flag" data-id="{{ $product->id }}" data-flag="is_best" {{ (int) $product->is_best === 1 ? 'checked' : '' }}>
                                                En çok satan
                                            </label>
                                            <label class="d-block mb-0">
                                                <input type="checkbox" class="homepage-flag" data-id="{{ $product->id }}" data-flag="new_product" {{ (int) $product->new_product === 1 ? 'checked' : '' }}>
                                                Yeni gelenler
                                            </label>
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
                                        <td colspan="7">Kayıt bulunamadı.</td>
                                    </tr>
                                  @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">{{ $products->links() }}</div>
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
    $(document).on('change', '.homepage-flag', function () {
        var isDemo = "{{ env('APP_VERSION') }}"
        if(isDemo == 0){
            toastr.error('Bu demo sürümdür. Herhangi bir değişiklik yapamazsınız.');
            this.checked = !this.checked;
            return;
        }
        var $el = $(this);
        $.ajax({
            type: "put",
            data: {
                _token: '{{ csrf_token() }}',
                flag: $el.data('flag'),
                value: $el.is(':checked') ? 1 : 0
            },
            url: "{{ url('/admin/product-homepage-flag') }}" + "/" + $el.data('id'),
            success: function (response) {
                if (response.counts) {
                    $.each(response.counts, function (flag, row) {
                        $('#flag-count-' + flag).text('Seçilen: ' + row.selected + ' · Anasayfada: ' + row.homepage);
                    });
                }
                toastr.success(response.message || 'Kaydedildi');
            },
            error: function () {
                $el.prop('checked', !$el.is(':checked'));
                toastr.error('Kaydedilemedi');
            }
        });
    });
    $(document).on('click', '.save-homepage-qty', function () {
        var flag = $(this).data('flag');
        var qty = $('.homepage-qty[data-flag="' + flag + '"]').val();
        $.ajax({
            type: "put",
            data: {
                _token: '{{ csrf_token() }}',
                flag: flag,
                qty: qty
            },
            url: "{{ url('/admin/product-homepage-qty') }}",
            success: function (response) {
                if (response.counts) {
                    $.each(response.counts, function (key, row) {
                        $('#flag-count-' + key).text('Seçilen: ' + row.selected + ' · Anasayfada: ' + row.homepage);
                        $('.homepage-qty[data-flag="' + key + '"]').val(row.qty);
                    });
                }
                toastr.success(response.message || 'Kaydedildi');
            },
            error: function () {
                toastr.error('Kaydedilemedi');
            }
        });
    });
</script>
@endsection
