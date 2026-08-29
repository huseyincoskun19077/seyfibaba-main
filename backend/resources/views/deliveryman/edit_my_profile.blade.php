@extends('deliveryman.master_layout')
@section('title')
<title>{{__('Edit Profile')}}</title>
@endsection
@section('deliveryman-content')
<!-- Main Content -->
<div class="main-content">
    <section class="section">
      <div class="section-header">
        <h1>{{__('Edit Profile')}}</h1>
      </div>

      <div class="section-body">
        <div class="row mt-4">
            <div class="col-12">
                <form action="{{ route('deliveryman.update-profile') }}" method="POST" enctype="multipart/form-data">
                    <div class="card">
                        <div class="card-body">
                                @csrf
                                @method('PUT')
                            <div class="row">
                                <div class="form-group col-12">
                                    <label>{{__('Delivery Man Image')}} <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control-file" name="man_image">
                                </div>
                                <div class="form-group col-6">
                                    <label>{{__('First Name')}} <span class="text-danger">*</span></label>
                                    <input type="text" id="fname" class="form-control"  name="fname" value="{{ $deliveryman->fname }}">
                                </div>
                                <div class="form-group col-6">
                                    <label>{{__('Last Name')}} <span class="text-danger">*</span></label>
                                    <input type="text" id="lname" class="form-control"  name="lname" value="{{ $deliveryman->lname }}">
                                </div>
                                <div class="form-group col-6">
                                    <label>{{__('Email')}} <span class="text-danger">*</span></label>
                                    <input type="text" id="email" class="form-control"  name="email" value="{{ $deliveryman->email }}" readonly>
                                </div>
                                <div class="form-group col-6">
                                    <label>{{__('Delivery Man Type')}} <span class="text-danger">*</span></label>
                                    <select class="form-control" name="man_type" id="man_type">
                                        <option value="freelancer" {{ $deliveryman->man_type=='freelancer'?'selected':'' }}>Freelancer</option>
                                        <option value="salary based" {{ $deliveryman->man_type=='salary based'?'selected':'' }}>Salary based</option>
                                    </select>
                                </div>
                                <div class="form-group col-6">
                                    <label>{{__('Identity Type')}} <span class="text-danger">*</span></label>
                                    <select class="form-control" name="idn_type" id="idn_type">
                                        <option value="passport" {{ $deliveryman->idn_type=='passport'?'selected':'' }}>Passport</option>
                                        <option value="driving license" {{ $deliveryman->idn_type=='driving license'?'selected':'' }}>Driving license</option>
                                        <option value="nid" {{ $deliveryman->idn_type=='passport'?'nid':'' }}>Nid</option>
                                    </select>
                                </div>
                                <div class="form-group col-6">
                                    <label>{{__('Identity Number')}} <span class="text-danger">*</span></label>
                                    <input type="text" id="idn_num" class="form-control"  name="idn_num" value="{{ $deliveryman->idn_num }}">
                                </div>
                                <div class="form-group col-6">
                                    <label>{{__('Identity Image')}} <span class="text-danger">*</span></label>
                                    <input type="file" id="idn_image" class="form-control-file"  name="idn_image">
                                </div>
                                <div class="form-group col-6">
                                    <label>{{__('Phone')}} <span class="text-danger">*</span></label>
                                    <input type="text" id="phone" class="form-control"  name="phone" value="{{ $deliveryman->phone }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn btn-primary">{{__('admin.Update')}}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            @if ($setting->map_status == 1)
            <div class="col-12">
                <div class="card profile-widget">
                    <div class="profile-widget-description">
                        <form action="{{ route('deliveryman.update.lat-long') }}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-6">
                                    <label>{{__('admin.Latitude')}} <span class="text-danger">*</span></label>
                                    <input type="text" id="latitude" class="form-control" value="{{ $deliveryman->latitude }}" name="latitude" readonly>
                                </div>

                                <div class="form-group col-6">
                                    <label>{{__('admin.Longitude')}} <span class="text-danger">*</span></label>
                                    <input type="text" id="longitude" class="form-control" value="{{ $deliveryman->longitude }}" name="longitude" readonly>
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Search Location')}}</label>
                                    <input type="text" id="search-input" class="form-control" placeholder="Search for a location">
                                </div>

                            </div>

                            <!-- Google Map -->
                            <div id="map" style="width:100%;height:400px;"></div><br>

                            <div class="row">
                                <div class="col-12">
                                    <button class="btn btn-primary">{{__('admin.Update')}}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
          @endif
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
              lat: parseFloat("{{ $deliveryman->latitude }}") || defaultLocation.lat,
              lng: parseFloat("{{ $deliveryman->longitude }}") || defaultLocation.lng
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

@endsection
