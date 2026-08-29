
@extends('admin.master_layout')
@section('title')
<title>{{__('admin.Seller Details')}}</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.Seller Details')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item active"><a href="{{ route('admin.seller-list') }}">{{__('admin.Seller List')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.Seller Details')}}</div>
            </div>
          </div>

          <div class="section-body">
            <a href="{{ route('admin.seller-list') }}" class="btn btn-primary"><i class="fas fa-list"></i> {{__('admin.Seller List')}}</a>
            <div class="row mt-5">
                <div class="col-md-3">
                  <div class="card card-statistic-1">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="card-wrap">
                      <div class="card-header">
                        <h4>{{__('admin.Total Product Sale')}}</h4>
                      </div>
                      <div class="card-body">
                        {{ $totalSoldProduct }}
                      </div>
                    </div>
                  </div>
                </div>

                    <div class="col-md-3">
                        <a href="{{ route('admin.withdraw-list',$seller->id) }}">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-danger">
                                <i class="far fa-newspaper"></i>
                                </div>
                                <div class="card-wrap">
                                <div class="card-header">
                                    <h4>{{__('admin.Total Withdraw')}}</h4>
                                </div>
                                <div class="card-body">
                                    {{ $setting->currency_icon }}{{ $totalWithdraw }}
                                </div>
                                </div>
                            </div>
                        </a>
                    </div>



                <div class="col-md-3">
                  <div class="card card-statistic-1">
                    <div class="card-icon bg-warning">
                      <i class="far fa-file"></i>
                    </div>
                    <div class="card-wrap">
                      <div class="card-header">
                        <h4>{{__('admin.Current Balance')}}</h4>
                      </div>
                      <div class="card-body">
                        {{ $setting->currency_icon }}{{ $totalAmount - $totalWithdraw }}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.product-by-seller',$user->id) }}">
                  <div class="card card-statistic-1">
                    <div class="card-icon bg-success">
                      <i class="fas fa-circle"></i>
                    </div>
                    <div class="card-wrap">
                      <div class="card-header">
                        <h4>{{__('admin.Total Products')}}</h4>
                      </div>
                      <div class="card-body">
                        {{ $seller->products->count() }}
                      </div>
                    </div>
                  </div>
                </a>
                </div>
              </div>
            <div class="row mt-sm-4">
                <div class="col-12 col-md-12 col-lg-5">
                  <div class="card profile-widget">
                    <div class="profile-widget-header">
                        @if ($user->image)
                        <img alt="image" src="{{ asset($user->image) }}" class="rounded-circle profile-widget-picture">
                        @endif
                      <div class="profile-widget-items">
                        <div class="profile-widget-item">
                          <div class="profile-widget-item-label">{{__('admin.Joined at')}}</div>
                          <div class="profile-widget-item-value">{{ $user->created_at->format('d M Y') }}</div>
                        </div>
                        <div class="profile-widget-item">
                          <div class="profile-widget-item-label">{{__('admin.Balance')}}</div>
                          <div class="profile-widget-item-value">{{ $setting->currency_icon }}{{ $totalAmount }}</div>
                        </div>
                      </div>
                    </div>
                    <div class="profile-widget-description">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <tr>
                                    <td>{{__('admin.Name')}}</td>
                                    <td>{{ $user->name }}</td>
                                </tr>
                                <tr>
                                    <td>{{__('admin.Email')}}</td>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <td>{{__('admin.Phone')}}</td>
                                    <td>{{ $user->phone }}</td>
                                </tr>
                                <tr>
                                    <td>Kayıt Kaynağı</td>
                                    <td>
                                        @if($seller->registration_source === 'call_center')
                                            <span class="badge badge-info">Çağrı Merkezi</span>
                                        @elseif($seller->registration_source === 'public_web')
                                            <span class="badge badge-primary">Web Kaydı</span>
                                        @else
                                            <span class="badge badge-secondary">Kendi Kaydı</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($seller->registration_source === 'call_center')
                                <tr>
                                    <td>Çağrı Merkezi Kullanıcısı</td>
                                    <td>
                                        @if($seller->registeredByAdmin)
                                            <strong>{{ $seller->registeredByAdmin->name }}</strong><br>
                                            <small class="text-muted">{{ $seller->registeredByAdmin->email }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($seller->quick_registration_note)
                                <tr>
                                    <td>Çağrı Merkezi Notu</td>
                                    <td>{{ $seller->quick_registration_note }}</td>
                                </tr>
                                @endif
                                @endif
                                @if($seller->isQuickOnboardingRegistration())
                                <tr>
                                    <td colspan="2" class="p-0 border-0">
                                        @include('call-center.partials.onboarding_status', [
                                            'vendor' => $seller,
                                            'title' => $seller->isCallCenterRegistration() ? 'Çağrı Merkezi Aktivasyon' : 'İlk Giriş Aktivasyon',
                                            'wrapperClass' => 'p-3',
                                            'resendSmsRoute' => route('admin.seller-resend-first-login-sms', $seller->id),
                                        ])
                                        @if($seller->isCallCenterRegistration())
                                        @include('call-center.partials.seller_progress', [
                                            'vendor' => $seller,
                                            'productCount' => $seller->products->count(),
                                            'wrapperClass' => 'px-3 pb-3',
                                        ])
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td>Satıcı Sözleşmesi</td>
                                    <td>
                                        @if($seller->seller_terms_accepted_at)
                                            <span class="badge badge-success">Onaylandı</span><br>
                                            <small class="text-muted">{{ $seller->seller_terms_accepted_at->format('d.m.Y H:i') }}</small>
                                            @if($seller->seller_terms_accepted_ip)
                                                <br><small class="text-muted">IP: {{ $seller->seller_terms_accepted_ip }}</small>
                                            @endif
                                        @else
                                            <span class="badge badge-warning">Onay Bekliyor</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{__('admin.User Status')}}</td>
                                    <td>
                                        @if($user->status == 1)
                                        <a href="javascript:;" onclick="manageCustomerStatus({{ $user->id }})">
                                            <input id="status_toggle" type="checkbox" checked data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.InActive')}}" data-onstyle="success" data-offstyle="danger">
                                        </a>
                                        @else
                                        <a href="javascript:;" onclick="manageCustomerStatus({{ $user->id }})">
                                            <input id="status_toggle" type="checkbox" data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.InActive')}}" data-onstyle="success" data-offstyle="danger">
                                        </a>
                                    @endif
                                    </td>
                                </tr>

                                <tr>
                                    <td>{{__('admin.Shop Status')}}</td>
                                    <td>
                                        @if($seller->status == 1)
                                        <a href="javascript:;" onclick="manageShopStatus({{ $seller->id }})">
                                            <input id="status_toggle" type="checkbox" checked data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.InActive')}}" data-onstyle="success" data-offstyle="danger">
                                        </a>
                                        @else
                                        <a href="javascript:;" onclick="manageShopStatus({{ $seller->id }})">
                                            <input id="status_toggle" type="checkbox" data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.InActive')}}" data-onstyle="success" data-offstyle="danger">
                                        </a>
                                    @endif
                                    </td>
                                </tr>


                            </table>
                        </div>
                    </div>

                    <div class="card-footer text-center">
                      <div class="font-weight-bold mb-2">{{__('admin.Follow')}} {{ $user->name }}</div>
                      @php
                          $colorId = 1;
                      @endphp
                      @foreach ($seller->socialLinks as $index => $socialLink)
                        @php
                            if($index % 4 == 0){
                                $colorId=1;
                            }
                            $color = "";
                            if($colorId == 1){
                                $color = "btn-facebook";
                            }else if($colorId == 2){
                                $color = "btn-twitter";
                            }else if($colorId == 3){
                                $color = "btn-instagram";
                            }else if($colorId == 4){
                                $color = "btn-github";
                            }
                        @endphp

                        <a href="{{ $socialLink->link }}" class="btn btn-social-icon {{ $color }} mr-1">
                            <i class="{{ $socialLink->icon }}"></i>
                        </a>

                        @php
                            $colorId++;
                        @endphp
                      @endforeach

                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h1>{{__('admin.Seller Action')}}</h1>
                            </div>
                            <div class="card-body text-center">
                                <div class="row">
                                    <div class="col-12">
                                        <a href="{{ route('admin.seller-shop-detail',$seller->id) }}" class="btn btn-success btn-block btn-lg my-2">{{__('admin.Go to Shop')}}</a>
                                    </div>
                                    <div class="col-12">
                                        <a href="{{ route('admin.seller-reviews',$seller->id) }}" class="btn btn-primary btn-block btn-lg my-2">{{__('admin.Seller Reviews')}}</a>
                                    </div>

                                    <div class="col-12">
                                        <a href="{{ route('admin.send-email-to-seller',$seller->id) }}" class="btn btn-warning btn-block btn-lg my-2">{{__('admin.Send Email')}}</a>
                                    </div>
                                    <div class="col-12">
                                        <a href="{{ route('admin.email-history',$seller->id) }}" class="btn btn-info btn-block btn-lg my-2">{{__('admin.Email Log')}}</a>
                                    </div>
                                    @if($seller->kyc_status && $seller->kyc_status !== 'not_submitted')
                                    <div class="col-12">
                                        <a href="{{ route('admin.kyc.show',$seller->id) }}" class="btn btn-dark btn-block btn-lg my-2">Satıcı KYC</a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                  </div>
                </div>


                <div class="col-12 col-md-12 col-lg-7">
                    <div class="card">
                        <form method="post" class="needs-validation" novalidate="" action="{{ route('admin.seller-update',$user->id) }}">
                            @method('put')
                            @csrf
                            <div class="card-header">
                                <h4>{{__('admin.Edit Profile')}}</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-6">
                                        <label>{{__('admin.Name')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="{{ $user->name }}" name="name">
                                    </div>

                                    <div class="form-group col-6">
                                        <label>{{__('admin.Email')}} <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" value="{{ $user->email }}" name="email" readonly>
                                    </div>

                                    <div class="form-group col-6">
                                        <label>{{__('admin.Phone')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="{{ $user->phone }}" name="phone">
                                    </div>

                                    <div class="form-group col-6">
                                        <label>{{__('admin.Address')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="{{ $user->address }}" name="address">
                                    </div>

                                </div>
                                <button class="btn btn-primary" type="submit">{{__('admin.Save Changes')}}</button>
                            </div>

                        </form>

                    @if ($setting->map_status == 1)
                    <div class="profile-widget">
                        <div class="profile-widget-description">
                            <!-- Google Map -->
                            <div id="map" style="width:100%;height:400px;"></div><br>
                        </div>
                    </div>
                    @endif
                    </div>
                </div>
              </div>
          </div>
        </section>
      </div>
      @if ($setting->map_status == 1)
      <!-- Add Google Maps API Script with Places API -->
      <script src="https://maps.googleapis.com/maps/api/js?key={{$setting->map_key}}&libraries=places&callback=initMap" async defer></script>

      <script>
          var map;
          var marker;
          var autocomplete;

          function initMap() {
              // Default location set to India
              var defaultLocation = { lat: 20.5937, lng: 78.9629 }; // Coordinates for India
              var initialLocation = {
                  lat: parseFloat("{{ $user->latitude }}") || defaultLocation.lat,
                  lng: parseFloat("{{ $user->longitude }}") || defaultLocation.lng
              };

              // Create the map centered on initial location (or default India)
              map = new google.maps.Map(document.getElementById('map'), {
                  center: initialLocation,
                  zoom: 6
              });

              // Create the marker positioned at the initial location
              marker = new google.maps.Marker({
                  position: initialLocation,
                  map: map,
                  draggable: true
              });

              // Initialize the search input for Google Places Autocomplete
              var input = document.getElementById('search-input');
              autocomplete = new google.maps.places.Autocomplete(input);
              autocomplete.bindTo('bounds', map);

              // Bias the autocomplete results towards the map's current view
              autocomplete.setFields(['geometry', 'name']);

              // Update map and marker on search result selection
              autocomplete.addListener('place_changed', function() {
                  var place = autocomplete.getPlace();

                  if (!place.geometry) {
                      alert("No details available for the input: '" + place.name + "'");
                      return;
                  }

                  // Move the map to the selected place
                  if (place.geometry.viewport) {
                      map.fitBounds(place.geometry.viewport);
                  } else {
                      map.setCenter(place.geometry.location);
                      map.setZoom(15);
                  }

                  // Move marker to the new location
                  marker.setPosition(place.geometry.location);

                  // Update latitude and longitude input fields
                  document.getElementById('latitude').value = place.geometry.location.lat();
                  document.getElementById('longitude').value = place.geometry.location.lng();
              });

              // Update latitude and longitude on marker drag event
              google.maps.event.addListener(marker, 'dragend', function() {
                  document.getElementById('latitude').value = marker.getPosition().lat();
                  document.getElementById('longitude').value = marker.getPosition().lng();
              });

              // Update latitude and longitude on map click event
              google.maps.event.addListener(map, 'click', function(event) {
                  placeMarker(event.latLng);
              });

              function placeMarker(location) {
                  // Move the marker to the clicked location
                  marker.setPosition(location);

                  // Update the input fields with the clicked location
                  document.getElementById('latitude').value = location.lat();
                  document.getElementById('longitude').value = location.lng();
              }
          }
      </script>
    @endif
<script>
    function manageCustomerStatus(id){
        var isDemo = "{{ env('APP_VERSION') }}"
        if(isDemo == 0){
            toastr.error('Bu demo sürümdür. Herhangi bir değişiklik yapamazsınız.');
            return;
        }
        $.ajax({
            type:"put",
            data: { _token : '{{ csrf_token() }}' },
            url:"{{url('/admin/customer-status/')}}"+"/"+id,
            success:function(response){
                toastr.success(response)
            },
            error:function(err){
                console.log(err);

            }
        })
    }

    function manageShopStatus(id){
        var isDemo = "{{ env('APP_VERSION') }}"
        if(isDemo == 0){
            toastr.error('Bu demo sürümdür. Herhangi bir değişiklik yapamazsınız.');
            return;
        }
        $.ajax({
            type:"put",
            data: { _token : '{{ csrf_token() }}' },
            url:"{{url('/admin/seller-status/')}}"+"/"+id,
            success:function(response){
                toastr.success(response)
            },
            error:function(err){
                console.log(err);

            }
        })
    }


</script>

<script>
    (function($) {
        "use strict";
        $(document).ready(function () {

            $("#country_id").on("change",function(){
                var countryId = $("#country_id").val();
                if(countryId){
                    $.ajax({
                        type:"get",
                        url:"{{url('/admin/state-by-country/')}}"+"/"+countryId,
                        success:function(response){
                            $("#state_id").html(response.states);
                            var response= "<option value=''>{{__('admin.Select a City')}}</option>";
                            $("#city_id").html(response);
                        },
                        error:function(err){
                            console.log(err);
                        }
                    })
                }else{
                    var response= "<option value=''>{{__('admin.Select a State')}}</option>";
                    $("#state_id").html(response);
                    var response= "<option value=''>{{__('admin.Select a City')}}</option>";
                    $("#city_id").html(response);
                }

            })

            $("#state_id").on("change",function(){
                var countryId = $("#state_id").val();
                if(countryId){
                    $.ajax({
                        type:"get",
                        url:"{{url('/admin/city-by-state/')}}"+"/"+countryId,
                        success:function(response){
                            console.log(response);
                            $("#city_id").html(response.cities);
                        },
                        error:function(err){
                            console.log(err);
                        }
                    })
                }else{
                    var response= "<option value=''>{{__('admin.Select a City')}}</option>";
                    $("#city_id").html(response);
                }

            })


        });
    })(jQuery);
</script>
@endsection
