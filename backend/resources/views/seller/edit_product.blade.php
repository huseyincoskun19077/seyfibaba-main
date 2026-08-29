@extends('seller.master_layout')
@section('title')
<title>{{__('admin.Products')}}</title>
@endsection
@section('seller-content')
      <div class="main-content seller-product-form">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Edit Product')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('seller.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Edit Product')}}</div>
            </div>
          </div>

          <div class="section-body">
            <div class="d-flex align-items-center mb-4">
              <a href="{{ route('seller.product.index') }}" class="btn btn-primary mr-2"><i class="fas fa-list"></i> {{__('admin.Products')}}</a>
              @include('seller.partials.ai_content_generator_button')
            </div>

            <form action="{{ route('seller.product.update',$product->id) }}" method="POST" enctype="multipart/form-data">
              @csrf
              @method('PUT')
              @php
                $activeTab = session('active_tab', old('active_tab', 'content'));
                if (! in_array($activeTab, ['content', 'images', 'specs', 'seo'], true)) {
                    $activeTab = 'content';
                }
                $tabFromUpdate = session()->has('active_tab');
              @endphp
              <input type="hidden" name="active_tab" id="active_tab" value="{{ $activeTab }}">

              <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link {{ $activeTab === 'content' ? 'active' : '' }}" id="content-tab" data-toggle="tab" href="#tab-content" role="tab" data-tab-key="content">
                    <i class="fas fa-edit mr-1"></i> İçerik
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link {{ $activeTab === 'images' ? 'active' : '' }}" id="images-tab" data-toggle="tab" href="#tab-images" role="tab" data-tab-key="images">
                    <i class="fas fa-images mr-1"></i> Görseller
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link {{ $activeTab === 'seo' ? 'active' : '' }}" id="seo-tab" data-toggle="tab" href="#tab-seo" role="tab" data-tab-key="seo">
                    <i class="fas fa-search mr-1"></i> SEO
                  </a>
                </li>
              </ul>

              <div class="tab-content" id="productTabContent">

                {{-- TAB: İçerik --}}
                <div class="tab-pane fade {{ $activeTab === 'content' ? 'show active' : '' }}" id="tab-content" role="tabpanel">
                  <div class="card">
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-12">
                            <label>{{__('admin.Short Name')}} <span class="text-danger">*</span></label>
                            <input type="text" id="short_name" class="form-control" name="short_name" value="{{ $product->short_name }}" placeholder="Berber Koltuğu">
                        </div>
                        <div class="form-group col-12">
                            <label>{{__('admin.Name')}} <span class="text-danger">*</span></label>
                            <input type="text" id="name" class="form-control" name="name" value="{{ $product->name }}" placeholder="Profesyonel Erkek Berber Koltuğu — Hidrolik">
                        </div>
                        <div class="form-group col-12">
                            <label>
                                {{__('admin.Slug')}} <span class="text-danger">*</span>
                                <a href="javascript:void(0)" class="ml-1 text-info" data-toggle="collapse" data-target="#slugHelpText" title="Slug nedir?">
                                    <i class="fas fa-info-circle"></i>
                                </a>
                            </label>
                            <input type="text" id="slug" class="form-control" name="slug" value="{{ $product->slug }}">
                            <div id="slugHelpText" class="collapse">
                                <small class="form-text text-muted">
                                    Slug, ürünün internet adresidir (Türkçesi: bağlantı adresi). Örnek: <code>profesyonel-berber-koltugu</code>. Ürün adını yazınca otomatik dolar; boşluk ve Türkçe karakter yerine tire kullanılır.
                                </small>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>{{__('admin.Category')}} <span class="text-danger">*</span></label>
                            <select name="category" class="form-control select2" id="category">
                                <option value="">{{__('admin.Select Category')}}</option>
                                @foreach ($categories as $category)
                                    <option {{ $product->category_id == $category->id ? 'selected' : '' }} value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>{{__('admin.Sub Category')}}</label>
                            <select name="sub_category" class="form-control select2" id="sub_category">
                                <option value="">{{__('admin.Select Sub Category')}}</option>
                                @foreach ($subCategories as $subCategory)
                                    <option {{ $product->sub_category_id == $subCategory->id ? 'selected' : '' }} value="{{ $subCategory->id }}">{{ $subCategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>{{__('admin.Child Category')}}</label>
                            <select name="child_category" class="form-control select2" id="child_category">
                                <option value="">{{__('admin.Select Child Category')}}</option>
                                @foreach ($childCategories as $childCategory)
                                    <option {{ $product->child_category_id == $childCategory->id ? 'selected' : '' }} value="{{ $childCategory->id }}">{{ $childCategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>{{__('admin.Brand')}}</label>
                            <select name="brand" class="form-control select2" id="brand">
                                <option value="">{{__('admin.Select Brand')}}</option>
                                @foreach ($brands as $brand)
                                    <option {{ $product->brand_id == $brand->id ? 'selected' : '' }} value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>{{__('admin.SKU')}}</label>
                            <input type="text" class="form-control" name="sku" value="{{ $product->sku }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Satış Fiyatı <span class="text-danger">* (TL)</span></label>
                            <input type="text" class="form-control" name="price" value="{{ $product->price }}">
                            <small class="text-muted">Paket için toplam fiyat.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label>İndirimli Fiyat <span class="text-danger">(TL)</span></label>
                            <input type="text" class="form-control" name="offer_price" value="{{ $product->offer_price }}" placeholder="0">
                            <small class="text-muted">İndirimli ürün yapmayacaksanız <strong>0</strong> yazın.</small>
                        </div>
                        @include('seller.partials.sale_unit_fields', ['saleUnitQty' => old('sale_unit_qty', $product->sale_unit_qty ?? 1)])
                        @include('seller.partials.seller_earnings_preview', ['commissionRate' => $commissionRate ?? 10])
                        <div class="form-group col-md-4">
                            <label>{{__('admin.Weight')}}(g) <small class="text-muted">(Opsiyonel)</small></label>
                            <input type="text" class="form-control" name="weight" value="{{ $product->weight }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Stok (kaç paket) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" min="0" value="{{ $product->qty }}">
                        </div>
                        <div class="form-group col-12">
                            <label>{{__('admin.Short Description')}} <span class="text-danger">*</span></label>
                            <textarea name="short_description" cols="30" rows="10" class="form-control text-area-5">{{ $product->short_description }}</textarea>
                        </div>
                        <div class="form-group col-12">
                            <label>{{__('admin.Long Description')}} <span class="text-danger">*</span></label>
                            <textarea name="long_description" cols="30" rows="10" class="summernote">{{ $product->long_description }}</textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- TAB: Görseller --}}
                <div class="tab-pane fade {{ $activeTab === 'images' ? 'show active' : '' }}" id="tab-images" role="tabpanel">
                  <div class="card">
                    <div class="card-header"><h4>Kapak Görseli</h4></div>
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-12 col-md-4 text-center">
                            <img id="preview-img" class="admin-img img-fluid rounded mb-3" src="{{ product_image_url($product->thumb_image) }}" alt="">
                        </div>
                        <div class="form-group col-12 col-md-8">
                            <div class="seller-photo-picker" data-target="thumb-input" data-preview="preview-img">
                              <label class="d-block font-weight-bold mb-2">Yeni kapak fotoğrafı</label>
                              <input type="file" id="thumb-input" class="d-none seller-photo-main" accept="image/jpeg,image/jpg,image/png,image/webp,image/*">
                              <div class="row">
                                <div class="col-6 pr-1">
                                  <button type="button" class="btn btn-primary btn-lg btn-block seller-photo-camera mb-2">
                                    <i class="fas fa-camera d-block mb-1"></i><span class="seller-photo-btn-text">Fotoğraf Çek</span>
                                  </button>
                                  <input type="file" class="d-none seller-photo-camera-input" accept="image/*" capture="environment">
                                </div>
                                <div class="col-6 pl-1">
                                  <button type="button" class="btn btn-outline-primary btn-lg btn-block seller-photo-gallery mb-2">
                                    <i class="fas fa-images d-block mb-1"></i><span class="seller-photo-btn-text">Galeriden Seç</span>
                                  </button>
                                  <input type="file" class="d-none seller-photo-gallery-input" accept="image/*">
                                </div>
                              </div>
                              <p class="text-muted small mb-2">JPEG, PNG veya WEBP · en fazla 5 MB</p>
                              <div class="seller-photo-previews row"></div>
                            </div>
                            <button type="button" class="btn btn-primary btn-lg btn-block mt-2" id="upload-thumb-btn">
                                <i class="fas fa-upload mr-1"></i> Thumbnail Yükle
                            </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card">
                    <div class="card-header"><h4>Ürün Galerisi</h4></div>
                    <div class="card-body">
                      <div class="seller-photo-picker mb-3" data-target="gallery-input" data-multiple="1">
                        <label class="d-block font-weight-bold mb-2">Yeni görseller ekle</label>
                        <input type="file" id="gallery-input" class="d-none seller-photo-main" accept="image/jpeg,image/jpg,image/png,image/webp,image/*" multiple>
                        <div class="row">
                          <div class="col-6 pr-1">
                            <button type="button" class="btn btn-primary btn-lg btn-block seller-photo-camera mb-2">
                              <i class="fas fa-camera d-block mb-1"></i><span class="seller-photo-btn-text">Fotoğraf Çek</span>
                            </button>
                            <input type="file" class="d-none seller-photo-camera-input" accept="image/*" capture="environment" multiple>
                          </div>
                          <div class="col-6 pl-1">
                            <button type="button" class="btn btn-outline-primary btn-lg btn-block seller-photo-gallery mb-2">
                              <i class="fas fa-images d-block mb-1"></i><span class="seller-photo-btn-text">Galeriden Seç</span>
                            </button>
                            <input type="file" class="d-none seller-photo-gallery-input" accept="image/*" multiple>
                          </div>
                        </div>
                        <p class="text-muted small mb-2">JPEG, PNG veya WEBP · en fazla 5 MB</p>
                        <div class="seller-photo-previews row"></div>
                      </div>
                      <button type="button" class="btn btn-success btn-lg btn-block" id="upload-gallery-btn">
                          <i class="fas fa-upload mr-1"></i> Galeriye Yükle
                      </button>
                      <hr>
                      <div class="row" id="gallery-container">
                          @if($product->gallery && $product->gallery->count() > 0)
                              @foreach($product->gallery as $image)
                                  <div class="col-6 col-md-3 mb-3" id="gallery-item-{{ $image->id }}">
                                      <div class="card shadow-sm">
                                          <img src="{{ asset($image->image) }}" class="card-img-top" alt="" style="height:150px; object-fit:cover;">
                                          <div class="card-body p-2 text-center">
                                              <button type="button" class="btn btn-danger btn-sm delete-gallery-btn" data-id="{{ $image->id }}">
                                                  <i class="fas fa-trash"></i> Sil
                                              </button>
                                          </div>
                                      </div>
                                  </div>
                              @endforeach
                          @endif
                      </div>
                      <div id="gallery-empty" class="{{ ($product->gallery && $product->gallery->count() > 0) ? 'd-none' : '' }}">
                          <p class="text-muted text-center py-4"><i class="fas fa-images mr-1"></i> Henüz galeri görseli eklenmemiş.</p>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- TAB: SEO --}}
                <div class="tab-pane fade {{ $activeTab === 'seo' ? 'show active' : '' }}" id="tab-seo" role="tabpanel">
                  <div class="card">
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-12">
                          <label>SEO Başlığı</label>
                          <input type="text" class="form-control" name="seo_title" value="{{ $product->seo_title }}" placeholder="Arama motorları için başlık (opsiyonel — boş bırakılırsa ürün adı kullanılır)">
                        </div>
                        <div class="form-group col-12">
                          <label>SEO Açıklaması</label>
                          <textarea name="seo_description" cols="30" rows="5" class="form-control" placeholder="Arama motorları için açıklama (opsiyonel)">{{ $product->seo_description }}</textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>

              @include('seller.partials.simple_color_variants', ['colorRows' => old('colors', $colorRows ?? [])])

              <div class="seller-sticky-save">
                  <button class="btn btn-primary btn-lg btn-block seller-save-btn">{{__('admin.Update')}}</button>
              </div>
            </form>
          </div>
        </section>
      </div>

@include('seller.partials.ai_content_generator_modal')

<script>
    (function($) {
        "use strict";
        var specification = '{{ $product->is_specification == 1 ? true : false }}';
        $(document).ready(function () {
            var serverTab = @json($activeTab);
            var fromUpdate = @json($tabFromUpdate);
            var savedTab = localStorage.getItem('sellerProductTab');

            if (fromUpdate) {
                localStorage.setItem('sellerProductTab', serverTab);
            } else if (savedTab && ['content', 'images', 'specs', 'seo'].indexOf(savedTab) !== -1 && savedTab !== serverTab) {
                $('#productTabs a[data-tab-key="' + savedTab + '"]').tab('show');
                $('#active_tab').val(savedTab);
            }

            $('#productTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var tabKey = $(e.target).data('tab-key') || 'content';
                $('#active_tab').val(tabKey);
                localStorage.setItem('sellerProductTab', tabKey);
            });

            $("#name").on("focusout",function(e){
                $("#slug").val(convertToSlug($(this).val()));
            })

            $('#productTabContent').closest('form').on('submit', function () {
                if (!$('#status_toggle').prop('checked')) {
                    $('#specification-box').find('select, input').prop('disabled', true);
                }
            });

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

            $(".removeExistSpecificationRow").on("click",function(){
                var specificationId = $(this).attr("data-specificationiId");
                $.ajax({
                    type:"put",
                    data: { _token : '{{ csrf_token() }}' },
                    url:"{{url('/seller/removed-product-exist-specification/')}}"+"/"+specificationId,
                    success:function(response){
                        toastr.success(response)
                        $("#existSpecificationBox-"+specificationId).remove();
                    },
                    error:function(err){ console.log(err); }
                })
            })
        });
    })(jQuery);

    function previewThumnailImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('preview-img');
            output.src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    };

    // Thumbnail yükleme (bağımsız AJAX)
    $("#upload-thumb-btn").on("click", function() {
        var file = $("#thumb-input")[0].files[0];
        if (!file) { toastr.warning("Lütfen bir küçük resim seçin."); return; }
        var formData = new FormData();
        formData.append("_token", "{{ csrf_token() }}");
        formData.append("thumb_image", file);
        var btn = $(this);
        btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Yükleniyor...');
        $.ajax({
            type: "POST",
            url: "{{ route('seller.product.update-thumbnail', $product->id) }}",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                btn.prop("disabled", false).html('<i class="fas fa-upload mr-1"></i> Thumbnail Yükle');
                toastr.success("Küçük resim güncellendi.");
            },
            error: function(xhr) {
                btn.prop("disabled", false).html('<i class="fas fa-upload mr-1"></i> Thumbnail Yükle');
                toastr.error("Yükleme hatası: " + (xhr.responseJSON?.message || "Bilinmeyen hata"));
            }
        });
    });

    // Galeri yükleme
    $("#upload-gallery-btn").on("click", function() {
        var files = $("#gallery-input")[0].files;
        if (files.length === 0) { toastr.warning("Lütfen en az bir görsel seçin."); return; }
        var formData = new FormData();
        formData.append("_token", "{{ csrf_token() }}");
        formData.append("product_id", "{{ $product->id }}");
        for (var i = 0; i < files.length; i++) { formData.append("images[]", files[i]); }
        var btn = $(this);
        btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Yükleniyor...');
        $.ajax({
            type: "POST",
            url: "{{ route('seller.store-product-gallery') }}",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                btn.prop("disabled", false).html('<i class="fas fa-upload mr-1"></i> Yükle');
                $("#gallery-input").val("");
                toastr.success("Görseller başarıyla yüklendi.");
                location.reload();
            },
            error: function(xhr) {
                btn.prop("disabled", false).html('<i class="fas fa-upload mr-1"></i> Yükle');
                toastr.error("Yükleme hatası.");
            }
        });
    });

    // Galeri silme
    $(document).on("click", ".delete-gallery-btn", function() {
        var id = $(this).data("id");
        if (!confirm("Bu görseli silmek istediğinize emin misiniz?")) return;
        $.ajax({
            type: "POST",
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: { _token: "{{ csrf_token() }}", _method: "DELETE" },
            url: "{{ url('/seller/delete-product-image') }}/" + id,
            success: function(response) {
                $("#gallery-item-" + id).fadeOut(300, function() {
                    $(this).remove();
                    if ($("#gallery-container").children().length === 0) { $("#gallery-empty").removeClass("d-none"); }
                });
                toastr.success("Görsel silindi.");
            },
            error: function(err) { toastr.error("Silme hatası."); }
        });
    });
</script>
@endsection
