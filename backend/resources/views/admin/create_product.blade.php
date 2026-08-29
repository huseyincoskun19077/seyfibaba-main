@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Products')}}</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Create Product')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Create Product')}}</div>
            </div>
          </div>

          <div class="section-body">
            <div class="d-flex align-items-center mb-4">
              <a href="{{ route('admin.product.index') }}" class="btn btn-primary mr-2"><i class="fas fa-list"></i> {{__('admin.Products')}}</a>
              @include('admin.partials.ai_content_generator_button')
            </div>

            <form action="{{ route('admin.product.store') }}" method="POST" enctype="multipart/form-data">
              @csrf

              {{-- Tab Navigation --}}
              <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="content-tab" data-toggle="tab" href="#tab-content" role="tab">
                    <i class="fas fa-edit mr-1"></i> İçerik
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="images-tab" data-toggle="tab" href="#tab-images" role="tab">
                    <i class="fas fa-images mr-1"></i> Görseller
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="specs-tab" data-toggle="tab" href="#tab-specs" role="tab">
                    <i class="fas fa-cogs mr-1"></i> Özellikler
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="seo-tab" data-toggle="tab" href="#tab-seo" role="tab">
                    <i class="fas fa-search mr-1"></i> SEO
                  </a>
                </li>
              </ul>

              {{-- Tab Content --}}
              <div class="tab-content" id="productTabContent">

                {{-- TAB: İçerik --}}
                <div class="tab-pane fade show active" id="tab-content" role="tabpanel">
                  <div class="card">
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-12">
                            <label>Satıcı (ürün kimin adına yüklensin?) <span class="text-danger">*</span></label>
                            <select name="vendor_id" class="form-control select2" id="vendor_id">
                                <option value="0" {{ (string) old('vendor_id', '0') === '0' ? 'selected' : '' }}>Admin ürünü (satıcısız)</option>
                                @foreach (($sellers ?? []) as $seller)
                                    <option value="{{ $seller->id }}" {{ (string) old('vendor_id') === (string) $seller->id ? 'selected' : '' }}>
                                        {{ $seller->shop_name }}@if($seller->user) — {{ $seller->user->name }}@endif (ID: {{ $seller->id }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Satıcı seçerseniz ürün o satıcının mağazasına ve sipariş/komisyon akışına bağlanır.</small>
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.Short Name')}} <span class="text-danger">*</span></label>
                            <input type="text" id="short_name" class="form-control" name="short_name" value="{{ old('short_name') }}">
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.Name')}} <span class="text-danger">*</span></label>
                            <input type="text" id="name" class="form-control" name="name" value="{{ old('name') }}">
                        </div>

                        <div class="form-group col-12">
                            <label>
                                {{__('admin.Slug')}} <span class="text-danger">*</span>
                                <a href="javascript:void(0)" class="ml-1 text-info" data-toggle="collapse" data-target="#slugHelpText" title="Slug nedir?">
                                    <i class="fas fa-info-circle"></i>
                                </a>
                            </label>
                            <input type="text" id="slug" class="form-control" name="slug" value="{{ old('slug') }}">
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
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>{{__('admin.Sub Category')}}</label>
                            <select name="sub_category" class="form-control select2" id="sub_category">
                                <option value="">{{__('admin.Select Sub Category')}}</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>{{__('admin.Child Category')}}</label>
                            <select name="child_category" class="form-control select2" id="child_category">
                                <option value="">{{__('admin.Select Child Category')}}</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>{{__('admin.Brand')}} </label>
                            <select name="brand" class="form-control select2" id="brand">
                                <option value="">{{__('admin.Select Brand')}}</option>
                                @foreach ($brands as $brand)
                                    <option {{ old('brand') == $brand->id ? 'selected' : '' }} value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-4">
                            <label>{{__('admin.SKU')}} </label>
                            <input type="text" class="form-control" name="sku">
                        </div>

                        <div class="form-group col-md-4">
                            <label>{{__('admin.Price')}} <span class="text-danger">* (TL)</span></label>
                            <input type="text" class="form-control" name="price" value="{{ old('price') }}">
                        </div>

                        <div class="form-group col-md-4">
                            <label>{{__('admin.Offer Price')}} <span class="text-danger"> (TL)</span></label>
                            <input type="text" class="form-control" name="offer_price" value="{{ old('offer_price') }}">
                        </div>

                        <div class="form-group col-md-6">
                            <label>{{__('admin.Stock Quantity')}} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" value="{{ old('quantity') }}">
                        </div>

                        <div class="form-group col-md-6">
                            <label>{{__('admin.Weight')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="weight" value="{{ old('weight') }}">
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.Short Description')}} <span class="text-danger">*</span></label>
                            <textarea name="short_description" cols="30" rows="10" class="form-control text-area-5">{{ old('short_description') }}</textarea>
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.Long Description')}} <span class="text-danger">*</span></label>
                            <textarea name="long_description" cols="30" rows="10" class="summernote">{{ old('long_description') }}</textarea>
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.Highlight')}}</label>
                            <div>
                                <input type="checkbox" name="top_product" id="top_product"> <label for="top_product" class="mr-3">{{__('admin.Top Product')}}</label>
                                <input type="checkbox" name="new_arrival" id="new_arrival"> <label for="new_arrival" class="mr-3">{{__('admin.New Arrival')}}</label>
                                <input type="checkbox" name="best_product" id="best_product"> <label for="best_product" class="mr-3">{{__('admin.Best Product')}}</label>
                                <input type="checkbox" name="is_featured" id="is_featured"> <label for="is_featured" class="mr-3">{{__('admin.Featured Product')}}</label>
                            </div>
                        </div>

                        <div class="form-group col-md-6">
                            <label>{{__('admin.Status')}} <span class="text-danger">*</span></label>
                            <select name="status" class="form-control">
                                <option value="1">{{__('admin.Active')}}</option>
                                <option value="0">{{__('admin.Inactive')}}</option>
                            </select>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- TAB: Görseller --}}
                <div class="tab-pane fade" id="tab-images" role="tabpanel">
                  <div class="card">
                    <div class="card-header"><h4>Küçük Resim (Thumbnail)</h4></div>
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-md-3">
                            <img id="preview-img" class="admin-img img-fluid rounded" src="{{ asset('uploads/website-images/preview.png') }}" alt="">
                        </div>
                        <div class="form-group col-md-9 d-flex align-items-center">
                            <input type="file" class="form-control-file" name="thumb_image" onchange="previewThumnailImage(event)">
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card">
                    <div class="card-header"><h4>Ürün Galerisi</h4></div>
                    <div class="card-body">
                      <div class="alert alert-info">
                          <i class="fas fa-info-circle mr-1"></i> Ürünü kaydettikten sonra galeri görselleri ekleyebilirsiniz.
                      </div>
                    </div>
                  </div>
                </div>

                {{-- TAB: Özellikler --}}
                <div class="tab-pane fade" id="tab-specs" role="tabpanel">
                  <div class="card">
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-12">
                            <label>{{__('admin.Specifications')}}</label>
                            <div>
                                <a href="javascript::void()" id="manageSpecificationBox">
                                    <input name="is_specification" id="status_toggle" type="checkbox" checked data-toggle="toggle" data-on="Açık" data-off="Kapalı" data-onstyle="success" data-offstyle="danger">
                                </a>
                            </div>
                        </div>

                        <div class="form-group col-12" id="specification-box">
                            <div class="row">
                                <div class="col-md-5">
                                    <label>{{__('admin.Key')}} <span class="text-danger">*</span></label>
                                    <select name="keys[]" class="form-control">
                                        @foreach ($specificationKeys as $specificationKey)
                                            <option value="{{ $specificationKey->id }}">{{ $specificationKey->key }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label>{{__('admin.Specification')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="specifications[]">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-success plus_btn" id="addNewSpecificationRow"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <div id="hidden-specification-box" class="d-none">
                            <div class="delete-specification-row">
                                <div class="row mt-2">
                                    <div class="col-md-5">
                                        <label>{{__('admin.Key')}} <span class="text-danger">*</span></label>
                                        <select name="keys[]" class="form-control">
                                            @foreach ($specificationKeys as $specificationKey)
                                                <option value="{{ $specificationKey->id }}">{{ $specificationKey->key }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label>{{__('admin.Specification')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="specifications[]">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger plus_btn deleteSpeceficationBtn"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- TAB: SEO --}}
                <div class="tab-pane fade" id="tab-seo" role="tabpanel">
                  <div class="card">
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-12">
                            <label>{{__('admin.SEO Title')}}</label>
                            <input type="text" class="form-control" name="seo_title" value="{{ old('seo_title') }}">
                        </div>

                        <div class="form-group col-12">
                            <label>{{__('admin.SEO Description')}}</label>
                            <textarea name="seo_description" cols="30" rows="10" class="form-control text-area-5">{{ old('seo_description') }}</textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>{{-- /tab-content --}}

              <div class="row mt-3">
                  <div class="col-12">
                      <button class="btn btn-primary">{{__('admin.Save')}}</button>
                  </div>
              </div>
            </form>
          </div>
        </section>
      </div>

@include('admin.partials.ai_content_generator_modal')

<script>
    (function($) {
        "use strict";
        var specification = true;
        $(document).ready(function () {
            $("#name").on("focusout",function(e){
                $("#slug").val(convertToSlug($(this).val()));
            })

            $("#category").on("change",function(){
                var categoryId = $("#category").val();
                if(categoryId){
                    $.ajax({
                        type:"get",
                        url:"{{url('/admin/subcategory-by-category/')}}"+"/"+categoryId,
                        success:function(response){
                            $("#sub_category").html(response.subCategories);
                            var response= "<option value=''>{{__('admin.Select Child Category')}}</option>";
                            $("#child_category").html(response);
                        },
                        error:function(err){
                            console.log(err);
                        }
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
                        url:"{{url('/admin/childcategory-by-subcategory/')}}"+"/"+SubCategoryId,
                        success:function(response){
                            $("#child_category").html(response.childCategories);
                        },
                        error:function(err){
                            console.log(err);
                        }
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


    function previewThumnailImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('preview-img');
            output.src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    };

</script>


@endsection
