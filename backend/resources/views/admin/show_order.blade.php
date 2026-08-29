@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Invoice')}}</title>
@endsection
<style>
    @media print {
        .section-header,
        .order-status,
        #sidebar-wrapper,
        .print-area,
        .main-footer,
        .new-product,
        .mainas,
        .plus,
        .action-btn,
        .delete-icon,
        .new-product,
        .additional_info {
            display:none!important;
        }

    }
</style>
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Invoice')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Invoice')}}</div>
            </div>
          </div>
          <div class="section-body">
            <div class="invoice">
              <div class="invoice-print">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="invoice-title">
                      <h2><img src="{{ asset($setting->logo) }}" alt="" width="120px"></h2>
                      <div class="invoice-number">Order #{{ $order->order_id }}</div>
                    </div>
                    <hr>
                    @php
                        $orderAddress = $order->orderAddress;
                    @endphp
                    @include('partials.order_invoice.addresses', ['orderAddress' => $orderAddress])
                    @include('partials.order_invoice.payment_block', ['order' => $order, 'setting' => $setting, 'invoiceContext' => 'admin'])
                  </div>
                </div>

                <div class="row mt-4">
                  <div class="col-md-12">
                    <div class="section-title">{{__('admin.Order Summary')}}</div>
                    @include('partials.order_invoice.summary_table', [
                        'order' => $order,
                        'setting' => $setting,
                        'invoiceContext' => 'admin',
                    ])
                    @include('partials.order_invoice.additional_info', ['order' => $order])

                    @include('partials.order_invoice.order_status_and_totals_row', [
                        'order' => $order,
                        'setting' => $setting,
                        'invoiceContext' => 'admin',
                        'orderDistinctSellerCount' => 1,
                        'sellerLinesSubtotal' => 0,
                        'deliverymans' => $deliverymans,
                    ])

                    @if(config('features.geliver_enabled'))
                    @include('partials.order_geliver_card', ['order' => $order, 'cardId' => null])
                    @endif

                  </div>
                </div>
                @if($order->payment_status != 1)
                <div class="row mt-3 new-product">
                    <div class="col-lg-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="section-title">{{ __('admin.Add New Product') }}</div>
                            <div>
                                <button class="btn btn-primary" type="button" data-toggle="modal" data-target="#exampleModalLong-2">
                                    {{ __('admin.Add New Product') }}
                                </button>
                            </div>
                        </div>
                        <form action="{{ route('admin.add-new-product-in-order', $order->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="product_id">{{ __('admin.Product') }}</label>
                                        <select name="product_id" class="form-control select2">
                                            <option value="">{{ __('admin.Select') }}</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('admin.Quantity') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="quantity" required>
                                    </div>
                                </div>
                            </div>

                            <button class="btn btn-success" type="submit">{{__('admin.New Order') }}</button>

                        </form>
                    </div>
                </div>
                @endif

              <div class="text-md-right print-area">
                <hr>
                <button class="btn btn-success btn-icon icon-left print_btn"><i class="fas fa-print"></i> {{__('admin.Print')}}</button>
                <button class="btn btn-danger btn-icon icon-left" data-toggle="modal" data-target="#deleteModal" onclick="deleteData({{ $order->id }})"><i class="fas fa-times"></i> {{__('admin.Delete')}}</button>
              </div>
            </div>
          </div>
          </div>
        </section>
      </div>

        <!-- Modal -->
        <div class="modal fade" id="exampleModalLong-2"  role="dialog"
          aria-labelledby="exampleModalLongTitle" aria-hidden="true">
          <div class="modal-dialog modal-dialog-two" role="document">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title" id="exampleModalLongTitle-1"> {{__('admin.Add New Product') }}</h5>
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                      </button>
                  </div>
                  <div class="modal-body">
                      <div class="modal-from">
                          <form action="{{ route('admin.product.store') }}" method="POST" enctype="multipart/form-data">
                          @csrf
                          <div class="row">
                              <div class="form-group col-12">
                                  <label>{{__('admin.Thumbnail Image Preview')}}</label>
                                  <div>
                                      <img id="preview-img" class="admin-img" src="{{ asset('uploads/website-images/preview.png') }}" alt="">
                                  </div>

                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.Thumnail Image')}} <span class="text-danger">*</span></label>
                                  <input type="file" class="form-control-file"  name="thumb_image" onchange="previewThumnailImage(event)" required>
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.Short Name')}} <span class="text-danger">*</span></label>
                                  <input type="text" id="short_name" class="form-control"  name="short_name" value="{{ old('short_name') }}" required>
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.Name')}} <span class="text-danger">*</span></label>
                                  <input type="text" id="name" class="form-control"  name="name" value="{{ old('name') }}" required>
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.Slug')}} <span class="text-danger">*</span></label>
                                  <input type="text" id="slug" class="form-control"  name="slug" value="{{ old('slug') }}">
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.Category')}} <span class="text-danger">*</span></label>
                                  <select name="category" class="form-control select2" id="category" required>
                                      <option value="">{{__('admin.Select Category')}}</option>
                                      @foreach ($categories as $category)
                                          <option value="{{ $category->id }}">{{ $category->name }}</option>
                                      @endforeach
                                  </select>
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.Sub Category')}}</label>
                                  <select name="sub_category" class="form-control select2" id="sub_category">
                                      <option value="">{{__('admin.Select Sub Category')}}</option>
                                  </select>
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.Child Category')}}</label>
                                  <select name="child_category" class="form-control select2" id="child_category">
                                      <option value="">{{__('admin.Select Child Category')}}</option>
                                  </select>
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.Brand')}} </label>
                                  <select name="brand" class="form-control select2" id="brand">
                                      <option value="">{{__('admin.Select Brand')}}</option>
                                      @foreach ($brands as $brand)
                                          <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                      @endforeach
                                  </select>
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.SKU')}} </label>
                                  <input type="text" class="form-control" name="sku">
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('Price')}} <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control" name="price" value="{{ old('price') }}" required>
                              </div>
                              <div class="form-group col-6">
                                  <label>{{__('admin.Offer Price')}}</label>
                                  <input type="text" class="form-control" name="offer_price" value="{{ old('offer_price') }}">
                              </div>



                              <div class="form-group col-6">
                                  <label>{{__('admin.Stock Quantity')}} <span class="text-danger">*</span></label>
                                  <input type="number" class="form-control" name="quantity" value="{{ old('quantity') }}" required>
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.Weight')}} <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control" name="weight" value="{{ old('weight') }}" required>
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.Short Description')}} <span class="text-danger">*</span></label>
                                  <textarea name="short_description" id="" cols="30" rows="10" class="form-control text-area-5">{{ old('short_description') }}</textarea>
                              </div>

                              <div class="form-group col-6">
                                  <label>{{__('admin.Long Description')}} <span class="text-danger">*</span></label>
                                  <textarea name="long_description" id="" cols="30" rows="10" class="form-control text-area-5">{{ old('long_description') }}</textarea>
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.Highlight')}}</label>
                                  <div>
                                      <input type="checkbox"name="top_product" id="top_product"> <label for="top_product" class="mr-3" >{{__('admin.Top Product')}}</label>

                                      <input type="checkbox" name="new_arrival" id="new_arrival"> <label for="new_arrival" class="mr-3" >{{__('admin.New Arrival')}}</label>

                                      <input type="checkbox" name="best_product" id="best_product"> <label for="best_product" class="mr-3" >{{__('admin.Best Product')}}</label>

                                      <input type="checkbox" name="is_featured" id="is_featured"> <label for="is_featured" class="mr-3" >{{__('admin.Featured Product')}}</label>
                                  </div>
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.Status')}} <span class="text-danger">*</span></label>
                                  <select name="status" class="form-control" required>
                                      <option value="1">{{__('admin.Active')}}</option>
                                      <option value="0">{{__('admin.Inactive')}}</option>
                                  </select>
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.SEO Title')}}</label>
                                  <input type="text" class="form-control" name="seo_title" value="{{ old('seo_title') }}">
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.SEO Description')}}</label>
                                  <textarea name="seo_description" id="" cols="30" rows="10" class="form-control text-area-5">{{ old('seo_description') }}</textarea>
                              </div>
                          </div>
                          <div class="row">
                              <div class="col-12">
                                    <button type="submit" class="modal-from-btm-btn">{{__('admin.Save') }}</button>
                              </div>
                          </div>
                      </form>

                      </div>
                  </div>
              </div>
          </div>
      </div>
      @if($setting->map_status == 1)
        <div class="modal fade" id="mapModal" tabindex="-1" role="dialog" aria-labelledby="mapModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title" id="mapModalLabel">{{$orderAddress->shipping_address}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                </div>
                <div class="modal-body">
                <!-- Google Map -->
                <div id="map" style="height: 400px; width: 100%;"></div>
                </div>
            </div>
            </div>
        </div>
      @endif

      @if($setting->map_status == 1)
      <script src="https://maps.googleapis.com/maps/api/js?key={{$setting->map_key}}&callback=initMap" async defer></script>

      <script>
        let map;

        function initMap() {
          // Set default coordinates (can be dynamic)
          const location = {
            lat: {{ $order->latitude ?? 0 }},
            lng: {{ $order->longitude ?? 0 }}
          };
           // Example: New York

          // Create a new map
          map = new google.maps.Map(document.getElementById("map"), {
            center: location,
            zoom: 10
          });

          // Create a marker on the map at the location
          const marker = new google.maps.Marker({
            position: location,
            map: map
          });
        }

        // Event listener for showing the map modal
        $('#mapModal').on('shown.bs.modal', function () {
          // Reinitialize the map when the modal is opened
          google.maps.event.trigger(map, "resize");

          // Optional: Center the map again after resizing
          map.setCenter({
            lat: {{ $order->latitude ?? 0 }},
            lng: {{ $order->longitude ?? 0 }}
          }); // Example: New York
        });

        // Optional: Update map coordinates based on dynamic values
        $('#viewMapButton').click(function() {
          // You can use dynamic latitude and longitude values here
          const lat = {{$order->latitude}}; // Replace with dynamic latitude
          const lng = {{$order->longitude}}; // Replace with dynamic longitude

          // Update map and marker position
          const location = { lat: lat, lng: lng };
          map.setCenter(location);
          new google.maps.Marker({
            position: location,
            map: map
          });
        });
      </script>
      @endif




      <script>
        function deleteData(id){
            $("#deleteForm").attr("action",'{{ url("admin/delete-order/") }}'+"/"+id)
        }
        (function($) {
            "use strict";
            $(document).ready(function() {

                $(".print_btn").on("click", function(){
                    $(".custom_click").click();
                    window.print()
                })

            });
        })(jQuery);

    </script>

    <script>
        function deleteData(id,order_id){
          console.log(order_id);
            $("#deleteForm").attr("action",'{{ url("admin/delete-order-product/") }}'+"/"+id+"/"+order_id)
        }
    </script>

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
            });

            $("#country").on("change",function(){
                var countryId = $("#country").val();
                if(countryId){
                    $.ajax({
                        type:"get",
                        url:"{{url('/admin/state-by-country/')}}"+"/"+countryId,
                        success:function(response){
                            $("#country").html(response.state);
                            var response= "<option value=''>{{__('admin.Select Child Category')}}</option>";
                            $("#state").html(response);
                        },
                        error:function(err){
                            console.log(err);
                        }
                    })
                }else{
                    var response= "<option value=''>{{__('admin.Select Sub Category')}}</option>";
                    $("#country").html(response);
                    var response= "<option value=''>{{__('admin.Select Child Category')}}</option>";
                    $("#state").html(response);
                }
            });



            $("#is_return").on('change',function(){
                var returnId = $("#is_return").val();
                if(returnId == 1){
                    $("#policy_box").removeClass('d-none');
                }else{
                    $("#policy_box").addClass('d-none');
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

    @if(config('features.geliver_enabled'))
    @include('partials.order_geliver_scripts', ['cargoBaseUrl' => url('admin/orders'), 'order' => $order])
    @endif

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
