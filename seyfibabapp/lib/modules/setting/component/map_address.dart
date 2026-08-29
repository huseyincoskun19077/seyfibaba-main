import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:location/location.dart' as loc;
import 'package:flutter_google_maps_webservices/places.dart';
import '/widgets/primary_button.dart';
import '../../../utils/utils.dart';
import '../../profile/controllers/address/address_cubit.dart';
import '../../profile/controllers/address/cubit/edit_address_cubit.dart';
import '../../profile/controllers/map/map_cubit.dart';

class AddressMapDialog extends StatefulWidget {
  const AddressMapDialog({super.key});

  @override
  AddressMapDialogState createState() => AddressMapDialogState();
}

class AddressMapDialogState extends State<AddressMapDialog> {
  GoogleMapController? _mapController;
  LatLng? _selectedPosition; // Make nullable to initialize it later.
  String _selectedAddress = '';
  late MapCubit aCubit;
  late AddressCubit addressCubit;
  late EditAddressCubit updateACubit;
  final Set<Marker> _markers = {}; // Use a Set to track markers.

  final TextEditingController _searchController = TextEditingController();
  List<Prediction> _placePredictions = [];
  late GoogleMapsPlaces _places;
  bool _isLocationLoaded = false;

  @override
  void initState() {
    super.initState();

    // Fetch cubits
    addressCubit = context.read<AddressCubit>();
    updateACubit = context.read<EditAddressCubit>();
    aCubit = context.read<MapCubit>();
    _places = GoogleMapsPlaces(apiKey: Utils.mapKey(context));
    if (aCubit.state.latitude != 0.0 && aCubit.state.longitude != 0.0) {
      debugPrint('state-lat-long ${aCubit.state.latitude}, ${aCubit.state.longitude}');
      _selectedPosition = LatLng(aCubit.state.latitude, aCubit.state.longitude);
      _setMarker(_selectedPosition!); // Set marker at existing coordinates
    } else {
      debugPrint('state-lat-long-empty ${aCubit.state.latitude}, ${aCubit.state.longitude}');
      _getUserLocation();
    }
  }

  Future<void> _getUserLocation() async {
    loc.Location location = loc.Location();
    var currentLocation = await location.getLocation();
    setState(() {
      _selectedPosition =
          LatLng(currentLocation.latitude!, currentLocation.longitude!);
      _isLocationLoaded = true;

      _setMarker(_selectedPosition!);
    });

    _mapController?.animateCamera(CameraUpdate.newLatLng(_selectedPosition!));
  }

  void _setMarker(LatLng position) {
    setState(() {
      _markers.clear(); // Clear previous markers to avoid duplicates
      _markers.add(Marker(
        markerId: const MarkerId('selected_position'),
        position: position,
      ));

      debugPrint('Marker added at: $position');
    });

    if (_mapController != null) {
      _mapController!.animateCamera(CameraUpdate.newLatLng(position));
    }
  }

  void _onSearchChanged(String query) async {
    if (query.isNotEmpty) {
      final response = await _places.autocomplete(query);
      if (response.status == 'OK') {
        setState(() {
          _placePredictions = response.predictions;
        });
      } else {
        setState(() {
          _placePredictions = [];
        });
      }
    } else {
      setState(() {
        _placePredictions = [];
        _selectedAddress = '';
      });
    }
  }

  void _selectPlace(Prediction prediction) async {
    Utils.closeKeyBoard(context);
    final details = await _places.getDetailsByPlaceId(prediction.placeId!);
    final lat = details.result.geometry?.location.lat;
    final lng = details.result.geometry?.location.lng;

    setState(() {
      _selectedPosition = LatLng(lat!, lng!);
      _selectedAddress = prediction.description!;
      _placePredictions = [];

      // Set marker to the newly selected position
      _setMarker(_selectedPosition!);
    });

    if (prediction.description != null) {
      aCubit.addLocation(prediction.description!);
      aCubit.updateLocation(prediction.description!);
      debugPrint('location-description ${prediction.description}');
      debugPrint('location-distance ${prediction.distanceMeters}');
      addressCubit.addressController.text = aCubit.state.location;
    }

    // Move the camera to the selected place
    _mapController?.animateCamera(CameraUpdate.newLatLng(_selectedPosition!));
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      insetPadding: const EdgeInsets.symmetric(horizontal: 10.0),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10.0)),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Search Field
            Padding(
              padding: const EdgeInsets.all(8.0),
              child: TextField(
                controller: _searchController,
                decoration:  InputDecoration(
                  labelText: Utils.formText(context, 'Search Location'),
                  hintText: Utils.formText(context, 'Search Location'),
                  border: const OutlineInputBorder(),
                ),
                onChanged: _onSearchChanged,
              ),
            ),
            // Displaying search predictions if they exist
            if (_placePredictions.isNotEmpty)
              SizedBox(
                height: 200,
                child: ListView.builder(
                  itemCount: _placePredictions.length,
                  itemBuilder: (context, index) {
                    final prediction = _placePredictions[index];
                    return Padding(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10.0, vertical: 12.0),
                      child: GestureDetector(
                        onTap: () => _selectPlace(prediction),
                        child: Text(prediction.description!),
                      ),
                    );
                  },
                ),
              ),
            // Google Map
            if (_selectedPosition != null)
              SizedBox(
                height: 400,
                width: double.infinity,
                child: GoogleMap(
                  onMapCreated: (controller) {
                    _mapController = controller;

                    // Ensure camera focuses on _selectedPosition after map is created
                    _mapController!.animateCamera(
                        CameraUpdate.newLatLng(_selectedPosition!));
                  },
                  onTap: (LatLng latLong) {
                    setState(() {
                      _selectedPosition = latLong;
                      _setMarker(latLong);
                    });
                    aCubit.addLatLong(latLong.latitude,latLong.longitude);
                    debugPrint('tap-lat-long ${latLong.longitude} - ${latLong.longitude}');
                  },
                  initialCameraPosition: CameraPosition(
                    target: _selectedPosition!,
                    zoom: 10.0,
                  ),
                  markers: _markers, // Add marker set to the map
                ),
              ),
            // Confirmation Button
            Padding(
              padding: const EdgeInsets.all(8.0),
              child: Column(
                children: [
                  // if (_selectedPosition != null)
                  //   Text(
                  //       'Selected Address: ${_selectedPosition!.latitude}, ${_selectedPosition!.longitude}'),
                  // const SizedBox(height: 10),
                  PrimaryButton(
                    onPressed: () {
                      if (_selectedPosition != null) {
                        aCubit..addLatLong(_selectedPosition?.latitude??0.0,_selectedPosition?.longitude??0.0)..getLocationFromLatLng();

                        // aCubit..addLatitude(_selectedPosition!.latitude)..addLongitude(_selectedPosition!.longitude);
                        debugPrint('state-lat-long-onTap ${_selectedPosition?.latitude}, ${_selectedPosition?.longitude}');
                        Navigator.pop(context, _selectedAddress);
                      }
                    },
                    text: 'Confirm Address',
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
