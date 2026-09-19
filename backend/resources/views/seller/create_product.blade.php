@extends('seller.master_layout')
@section('title')
<title>{{__('admin.Products')}}</title>
@endsection
@section('seller-content')
      <div class="main-content seller-product-form">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Create Product')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Create Product')}}</div>
            </div>
          </div>

          <div class="section-body">
            @if ($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <div class="d-flex flex-wrap align-items-center mb-3" style="gap:8px;">
              <a href="{{ route('seller.product.index') }}" class="btn btn-primary"><i class="fas fa-list"></i> {{__('admin.Products')}}</a>
              @include('seller.partials.ai_content_generator_button')
            </div>

            <form action="{{ route('seller.product.store') }}" method="POST" enctype="multipart/form-data">
              @csrf

              <div class="card mb-3">
                <div class="card-header"><h4 class="mb-0">Ürün bilgileri</h4></div>
                <div class="card-body">
                  <div class="row">
                    <div class="form-group col-12">
                        <label>Ürün adı <span class="text-danger">*</span></label>
                        <input type="text" id="name" class="form-control" name="name" value="{{ old('name') }}" placeholder="Örn: Profesyonel Erkek Berber Koltuğu — Hidrolik" required>
                        <small class="text-muted">Liste ve aramada görünen ad. Kısa ad ve bağlantı adresi bundan otomatik oluşur.</small>
                        <input type="hidden" id="short_name" name="short_name" value="{{ old('short_name') }}">
                        <input type="hidden" id="slug" name="slug" value="{{ old('slug') }}">
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.Category')}} <span class="text-danger">*</span></label>
                        <select name="category" class="form-control select2" id="category" required>
                            <option value="">{{__('admin.Select Category')}}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.Sub Category')}}</label>
                        <select name="sub_category" class="form-control select2" id="sub_category">
                            <option value="">{{__('admin.Select Sub Category')}}</option>
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.Child Category')}}</label>
                        <select name="child_category" class="form-control select2" id="child_category">
                            <option value="">{{__('admin.Select Child Category')}}</option>
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.Brand')}}</label>
                        <select name="brand" class="form-control select2" id="brand">
                            <option value="">{{__('admin.Select Brand')}}</option>
                            @foreach ($brands as $brand)
                                <option {{ old('brand') == $brand->id ? 'selected' : '' }} value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-4">
                        <label>{{__('admin.SKU')}} <small class="text-muted">(opsiyonel)</small></label>
                        <input type="text" class="form-control" name="sku" value="{{ old('sku') }}">
                    </div>
                    <div class="form-group col-12 col-md-4">
                        <label>Satış Fiyatı <span class="text-danger">* (TL)</span></label>
                        <input type="text" inputmode="decimal" class="form-control" name="price" value="{{ old('price') }}" required>
                        <small class="text-muted">Paket satış fiyatı. Örn: 5’li paket 600 TL.</small>
                    </div>
                    <div class="form-group col-12 col-md-4">
                        <label>İndirimli Fiyat (TL)</label>
                        <input type="text" inputmode="decimal" class="form-control" name="offer_price" value="{{ old('offer_price', '0') }}" placeholder="0">
                        <small class="text-muted">İndirim yoksa <strong>0</strong> bırakın.</small>
                    </div>
                    @include('seller.partials.sale_unit_fields', ['saleUnitQty' => old('sale_unit_qty', 1)])
                    @include('seller.partials.seller_earnings_preview', ['commissionRate' => $commissionRate ?? 10])
                    <div class="form-group col-12 col-md-6">
                        <label>Stok (kaç paket) <span class="text-danger">*</span></label>
                        <input type="number" inputmode="numeric" class="form-control" name="quantity" value="{{ old('quantity') }}" required>
                        <small class="text-muted">Satışa sunacağınız paket adedi.</small>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.Weight')}}(g) <small class="text-muted">(opsiyonel)</small></label>
                        <input type="text" inputmode="decimal" class="form-control" name="weight" value="{{ old('weight') }}">
                    </div>
                    @include('seller.partials.delivery_info_field', ['deliveryInfo' => old('delivery_info'), 'wrapperClass' => 'col-12'])
                    <div class="form-group col-12">
                        <label>{{__('admin.Short Description')}} <span class="text-danger">*</span></label>
                        <textarea name="short_description" cols="30" rows="4" class="form-control" required>{{ old('short_description') }}</textarea>
                        <small class="text-muted">SEO açıklaması ürün adı + bu metinden otomatik üretilir.</small>
                    </div>
                    <div class="form-group col-12">
                        <label>{{__('admin.Long Description')}} <span class="text-danger">*</span></label>
                        <textarea name="long_description" cols="30" rows="10" class="summernote">{{ old('long_description') }}</textarea>
                    </div>
                  </div>
                </div>
              </div>

              @include('seller.partials.simple_color_variants', ['colorRows' => old('colors', [])])

              <div class="card mb-3">
                <div class="card-header"><h4 class="mb-0">Fotoğraflar <span class="text-danger">*</span></h4></div>
                <div class="card-body">
                  <p class="text-muted mb-3">Kapak fotoğrafı zorunludur. Ek fotoğraflar isteğe bağlıdır. Renk fotoğraflarını yukarıdaki renk satırlarından ekleyin.</p>
                  <div class="row">
                    <div class="form-group col-12 col-md-4 text-center">
                        <img id="preview-img" class="admin-img img-fluid rounded mb-3" src="{{ asset('uploads/website-images/preview.png') }}" alt="">
                    </div>
                    <div class="form-group col-12 col-md-8">
                        @include('seller.partials.photo_picker', [
                          'inputName' => 'thumb_image',
                          'inputId' => 'thumb_image',
                          'previewId' => 'preview-img',
                          'required' => true,
                          'label' => 'Kapak fotoğrafı (zorunlu)',
                        ])
                    </div>
                  </div>
                  <hr>
                  @include('seller.partials.photo_picker', [
                    'inputName' => 'images[]',
                    'inputId' => 'gallery_images',
                    'multiple' => true,
                    'label' => 'Ek ürün fotoğrafları (isteğe bağlı)',
                  ])
                </div>
              </div>

              <div class="seller-sticky-save">
                  <button class="btn btn-primary btn-lg btn-block seller-save-btn">{{__('admin.Save')}}</button>
              </div>
            </form>
          </div>
        </section>
      </div>

@include('seller.partials.ai_content_generator_modal')

<script>
    (function($) {
        "use strict";
        var specification = true;
        $(document).ready(function () {
            $("#name").on("input focusout",function(e){
                var n = $(this).val() || "";
                $("#slug").val(convertToSlug(n));
                $("#short_name").val(n.substring(0, 80));
            });
            // first paint from old()
            if ($("#name").val()) {
                $("#slug").val(convertToSlug($("#name").val()));
                if (!$("#short_name").val()) {
                    $("#short_name").val(($("#name").val() || "").substring(0, 80));
                }
            }

            $("#category").on("change",function(){
                var categoryId = $("#category").val();
                if(categoryId){
                    $.ajax({
                        type:"get",
                        url:"{{url('/seller/subcategory-by-category/')}}"+"/"+categoryId,
                        success:function(response){
                            $("#sub_category").html(response.subCategories);
                            var response= "<option value=''>{{__('admin.Select Child Category')}}</option>";
                            $("#child_category").html(response);
                        },
                        error:function(err){ console.log(err); }
                    })
                }else{
                    var response= "<option value=''>{{__('admin.Select Sub Category')}}</option>";
                    $("#sub_category").html(response);
                    var response= "<option value=''>{{__('admin.Select Child Category')}}</option>";
                    $("#child_category").html(response);
                }
            })

            $("#sub_category").on("change",function(){
                var SubCategoryId = $("#sub_category").val();
                if(SubCategoryId){
                    $.ajax({
                        type:"get",
                        url:"{{url('/seller/childcategory-by-subcategory/')}}"+"/"+SubCategoryId,
                        success:function(response){
                            $("#child_category").html(response.childCategories);
                        },
                        error:function(err){ console.log(err); }
                    })
                }else{
                    var response= "<option value=''>{{__('admin.Select Child Category')}}</option>";
                    $("#child_category").html(response);
                }
            })

            $("#addNewSpecificationRow").on('click',function(){
                var html = $("#hidden-specification-box").html();
                $("#specification-box").append(html);
            })

            $(document).on('click', '.deleteSpeceficationBtn', function () {
                $(this).closest('.delete-specification-row').remove();
            });

            $("#manageSpecificationBox").on("click",function(){
                if(specification){
                    specification = false;
                    $("#specification-box").addClass('d-none');
                }else{
                    specification = true;
                    $("#specification-box").removeClass('d-none');
                }
            })
        });
    })(jQuery);
</script>
@endsection
