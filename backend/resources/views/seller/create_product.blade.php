@extends('seller.master_layout')
@section('title')
<title>{{__('admin.Products')}}</title>
@endsection
@section('seller-content')
@include('seller.partials.product_form_styles')
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
            </div>

            @if (isset($aiEnabled) && $aiEnabled)
            <div class="spf-ai-card">
              <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:12px;">
                <div>
                  <h5><i class="fas fa-robot mr-1"></i> Yapay zeka ile doldur</h5>
                  <p class="mb-0 spf-hint">Önce ürün adını yazın. Kuaför Tedarik (berber / kuaför / salon) pazaryerine uygun başlık, açıklama ve SEO üretir.</p>
                </div>
                @include('seller.partials.ai_content_generator_button')
              </div>
            </div>
            @endif

            <form action="{{ route('seller.product.store') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <input type="hidden" id="short_name" name="short_name" value="{{ old('short_name') }}">
              <input type="hidden" id="slug" name="slug" value="{{ old('slug') }}">
              <input type="hidden" name="seo_title" id="seo_title" value="{{ old('seo_title') }}">
              <input type="hidden" name="seo_description" id="seo_description" value="{{ old('seo_description') }}">
              <input type="hidden" name="tags" id="tags" value="{{ old('tags') }}">

              <div class="spf-step">
                <div class="spf-step-head">
                  <span class="spf-step-num">1</span>
                  <div>
                    <h4>Temel bilgiler</h4>
                    <p>Zorunlu alanlar kırmızı etiketli. Ad yazınca bağlantı adresi otomatik oluşur.</p>
                  </div>
                </div>
                <div class="spf-step-body">
                  <div class="row">
                    <div class="form-group col-12">
                        <label>Ürün adı <span class="spf-req">zorunlu</span></label>
                        <input type="text" id="name" class="form-control" name="name" value="{{ old('name') }}" placeholder="Örn: Profesyonel Erkek Berber Koltuğu — Hidrolik" required>
                        <div class="spf-hint">Liste ve aramada görünen ad.</div>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.Category')}} <span class="spf-req">zorunlu</span></label>
                        <select name="category" class="form-control select2" id="category" required>
                            <option value="">{{__('admin.Select Category')}}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.Sub Category')}} <span class="spf-opt">opsiyonel</span></label>
                        <select name="sub_category" class="form-control select2" id="sub_category">
                            <option value="">{{__('admin.Select Sub Category')}}</option>
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.Child Category')}} <span class="spf-opt">opsiyonel</span></label>
                        <select name="child_category" class="form-control select2" id="child_category">
                            <option value="">{{__('admin.Select Child Category')}}</option>
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.Brand')}} <span class="spf-opt">opsiyonel</span></label>
                        <select name="brand" class="form-control select2" id="brand">
                            <option value="">{{__('admin.Select Brand')}}</option>
                            @foreach ($brands as $brand)
                                <option {{ old('brand') == $brand->id ? 'selected' : '' }} value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-4">
                        <label>Satış fiyatı (₺) <span class="spf-req">zorunlu</span></label>
                        <input type="text" inputmode="decimal" class="form-control" name="price" value="{{ old('price') }}" required>
                        <div class="spf-hint">Paket satış fiyatı. Örn: 5’li paket 600 TL.</div>
                    </div>
                    <div class="form-group col-12 col-md-4">
                        <label>İndirimli fiyat (₺) <span class="spf-opt">opsiyonel</span></label>
                        <input type="text" inputmode="decimal" class="form-control" name="offer_price" value="{{ old('offer_price', '0') }}" placeholder="0">
                        <div class="spf-hint">İndirim yoksa 0 bırakın.</div>
                    </div>
                    <div class="form-group col-12 col-md-4">
                        <label>Stok (paket) <span class="spf-req">zorunlu</span></label>
                        <input type="number" inputmode="numeric" class="form-control" name="quantity" value="{{ old('quantity') }}" required>
                    </div>
                    @include('seller.partials.sale_unit_fields', ['saleUnitQty' => old('sale_unit_qty', 1)])
                    @include('seller.partials.seller_earnings_preview', ['commissionRate' => $commissionRate ?? 10])
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.SKU')}} <span class="spf-opt">opsiyonel</span></label>
                        <input type="text" class="form-control" name="sku" value="{{ old('sku') }}">
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label>{{__('admin.Weight')}}(g) <span class="spf-opt">opsiyonel</span></label>
                        <input type="text" inputmode="decimal" class="form-control" name="weight" value="{{ old('weight') }}">
                    </div>
                    @include('seller.partials.delivery_info_field', ['deliveryInfo' => old('delivery_info'), 'wrapperClass' => 'col-12'])
                    <div class="form-group col-12">
                        <label>Açıklama <span class="spf-req">zorunlu</span></label>
                        <textarea name="short_description" id="short_description" cols="30" rows="4" class="form-control" required placeholder="Ürünü kısaca anlatın. Site’de “devamını gör” bu metinden açılır.">{{ old('short_description') }}</textarea>
                        <input type="hidden" name="long_description" id="long_description" value="{{ old('long_description') }}">
                        <div class="spf-hint">Kısa ve detaylı açıklama aynıdır; SEO de buradan üretilir.</div>
                    </div>
                  </div>
                </div>
              </div>

              @include('seller.partials.simple_product_variants', [
                'colorRows' => old('colors', []),
                'optionGroups' => collect(old('option_groups', []))->map(function ($g) {
                    if (! is_array($g)) {
                        return null;
                    }
                    $items = $g['rows'] ?? $g['items'] ?? [];
                    $rows = [];
                    foreach ((array) $items as $item) {
                        if (! is_array($item)) {
                            continue;
                        }
                        $n = trim((string) ($item['name'] ?? ''));
                        if ($n === '') {
                            continue;
                        }
                        $rows[] = ['name' => $n, 'price' => $item['price'] ?? ''];
                    }

                    return ['name' => $g['name'] ?? '', 'rows' => $rows];
                })->filter()->values()->all(),
              ])

              <div class="spf-step">
                <div class="spf-step-head">
                  <span class="spf-step-num">3</span>
                  <div>
                    <h4>Fotoğraflar</h4>
                    <p>Kapak zorunlu. Renk fotoğraflarını varyantlardan ekleyin.</p>
                  </div>
                </div>
                <div class="spf-step-body">
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
                          'label' => 'Kapak fotoğrafı',
                        ])
                        <span class="spf-req">zorunlu</span>
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
                  <button class="btn btn-primary btn-lg btn-block seller-save-btn">Ürünü kaydet ve yayınla</button>
              </div>
            </form>
          </div>
        </section>
      </div>

@include('seller.partials.ai_content_generator_modal')

<script>
    (function($) {
        "use strict";
        $(document).ready(function () {
            function syncLongFromShort() {
                var t = ($("#short_description").val() || "").trim();
                var html = t ? ("<p>" + $("<div>").text(t).html().replace(/\n/g, "<br>") + "</p>") : "";
                $("#long_description").val(html);
            }
            $("#short_description").on("input change", syncLongFromShort);
            syncLongFromShort();
            $("form").on("submit", syncLongFromShort);

            $("#name").on("input focusout",function(){
                var n = $(this).val() || "";
                $("#slug").val(convertToSlug(n));
                $("#short_name").val(n.substring(0, 80));
            });
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
                            $("#child_category").html("<option value=''>{{__('admin.Select Child Category')}}</option>");
                        }
                    })
                }else{
                    $("#sub_category").html("<option value=''>{{__('admin.Select Sub Category')}}</option>");
                    $("#child_category").html("<option value=''>{{__('admin.Select Child Category')}}</option>");
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
                        }
                    })
                }else{
                    $("#child_category").html("<option value=''>{{__('admin.Select Child Category')}}</option>");
                }
            })
        });
    })(jQuery);

    function convertToSlug(Text){
        Text = Text.toLowerCase();
        Text = Text.replace(/[^a-zA-ZğüşöçıİĞÜŞÖÇ0-9]+/g,'-');
        Text = Text.replace(/^-+|-+$/g,'');
        return Text;
    }
</script>
@endsection
