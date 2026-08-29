@extends('admin.master_layout')
@section('title')
<title>{{__('admin.My Profile')}}</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>{{__('admin.My Profile')}}</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">{{__('admin.My Profile')}}</div>
            </div>
          </div>
          <div class="section-body">
            <div class="row mt-sm-4">
              <div class="col-8">
                <div class="card profile-widget">
                  <div class="profile-widget-header">
                      @if ($admin->image)
                      <img alt="image" src="{{ asset($admin->image) }}" class="rounded-circle profile-widget-picture">
                      @else
                      <img alt="image" src="{{ asset($defaultProfile->image) }}" class="rounded-circle profile-widget-picture">
                      @endif
                  </div>
                  <div class="profile-widget-description">
                    <form action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" method="POST">
                    @csrf
                    @method('PUT')
                        <div class="row">
                            <div class="form-group col-12">
                            <label>{{__('admin.New Image')}}</label>
                            <input type="file" class="form-control-file" name="image">
                            </div>
                            <div class="form-group col-12">
                            <label>{{__('admin.Name')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" value="{{ $admin->name }}" name="name">
                            </div>

                            <div class="form-group col-12">
                                <label>{{__('admin.Email')}} <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" value="{{ $admin->email }}" name="email">
                            </div>

                            <div class="form-group col-12">
                            <label>{{__('admin.Password')}}</label>
                            <input type="password" class="form-control" name="password">
                            </div>

                            <div class="form-group col-12">
                                <label>{{__('admin.Confirm Password')}}</label>
                                <input type="password" class="form-control" name="password_confirmation">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button class="btn btn-primary">{{__('admin.Update')}}</button>
                            </div>
                        </div>
                        </div>
                    </form>
                </div>
              </div>
              @if ($setting->map_status == 1)
                <div class="col-4">
                    <div class="card profile-widget">
                        <div class="profile-widget-description">
                            <form action="{{ route('admin.update.lat-long') }}" enctype="multipart/form-data" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-12">
                                        <label>{{__('admin.Latitude')}} <span class="text-danger">*</span></label>
                                        <input type="text" id="latitude" class="form-control" value="{{ $admin->latitude }}" name="latitude" readonly>
                                    </div>

                                    <div class="form-group col-12">
                                        <label>{{__('admin.Longitude')}} <span class="text-danger">*</span></label>
                                        <input type="text" id="longitude" class="form-control" value="{{ $admin->longitude }}" name="longitude" readonly>
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
                    lat: parseFloat("{{ $admin->latitude }}") || defaultLocation.lat,
                    lng: parseFloat("{{ $admin->longitude }}") || defaultLocation.lng
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
