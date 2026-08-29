import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:http/http.dart' as http;
import 'package:shop_o/modules/profile/controllers/map/map_cubit.dart';
import 'package:shop_o/modules/profile/controllers/map/map_state_model.dart';
import '../../widgets/custom_text.dart';

import '../../core/remote_urls.dart';
import '../../utils/utils.dart';
import '../../widgets/app_bar_leading.dart';
import 'dart:async';

class TrackingLocationScreen extends StatefulWidget {
  const TrackingLocationScreen({super.key});


  @override
  TrackingLocationScreenState createState() => TrackingLocationScreenState();
}

class TrackingLocationScreenState extends State<TrackingLocationScreen> {
  GoogleMapController? _mapController;
  final Set<Marker> _markers = {};
  final Set<Polyline> _polyLines = {};
  late LatLng sourceLocation;
  late LatLng destinationLocation;
  String totalDistance = '';
  String totalDuration = '';
  late MapCubit pCubit;
  LatLng? previousLocation; // To keep track of the previous location
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    pCubit = context.read<MapCubit>();
    initLatLng();
    _setMarkers();
    _drawRoute();
    pCubit.isOpen(true);
    previousLocation = sourceLocation;
  }

  @override
  void dispose() {
    pCubit.isOpen(false);
    _timer?.cancel(); // Dispose the timer
    super.dispose();
  }

  initLatLng() {
    sourceLocation = LatLng(pCubit.state.latitude, pCubit.state.longitude);
    destinationLocation = LatLng(pCubit.state.dLatitude, pCubit.state.dLongitude);
    // destinationLocation = LatLng(widget.order.billingLatitude, widget.order.billingLongitude);
    //debugPrint('source-lat-long ${pCubit.state.latitude} - ${pCubit.state.latitude}');
    //debugPrint('destination-lat-long ${pCubit.state.dLatitude} - ${pCubit.state.dLongitude}');
  }

  void _setMarkers() {
    setState(() {
      _markers.add(Marker(
        markerId: const MarkerId('source'),
        position: sourceLocation,
        infoWindow: const InfoWindow(title: 'Source'),
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
      ));
      _markers.add(Marker(
        markerId: const MarkerId('destination'),
        position: destinationLocation,
        infoWindow: const InfoWindow(title: 'Destination'),
      ));
    });
  }

  Future<void> _drawRoute() async {
    try {
      List<LatLng> routeCoords =
          await getRouteCoordinates(sourceLocation, destinationLocation);

      setState(() {
        _polyLines.add(Polyline(
          polylineId: const PolylineId('route'),
          points: routeCoords,
          // color: Colors.blueAccent,
          color: Utils.dynamicPrimaryColor(context),
          width: 5,
        ));
      });
    } catch (e) {
      debugPrint('Error fetching route: $e');
      Utils.errorSnackBar(context, e.toString());
    }
  }

  // Function to animate the marker movement
  void _animateMarker(LatLng newLocation) {
    const int steps = 60;
    const duration = Duration(seconds: 2); // Adjust for desired speed
    final stepDuration = duration ~/ steps;
    int currentStep = 0;

    _timer?.cancel();
    _timer = Timer.periodic(stepDuration, (timer) {
      double t = currentStep / steps;
      double lat = previousLocation!.latitude +
          (newLocation.latitude - previousLocation!.latitude) * t;
      double lng = previousLocation!.longitude +
          (newLocation.longitude - previousLocation!.longitude) * t;

      LatLng interpolatedLocation = LatLng(lat, lng);

      setState(() {
        _markers.removeWhere(
            (marker) => marker.markerId == const MarkerId('source'));
        _markers.add(Marker(
          markerId: const MarkerId('source'),
          position: interpolatedLocation,
          infoWindow: const InfoWindow(title: 'Source'),
          icon:
              BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
        ));
      });

      currentStep++;
      if (currentStep >= steps) {
        timer.cancel();
        previousLocation = newLocation; // Update to the final location
      }
    });
  }

  Future<List<LatLng>> getRouteCoordinates(
      LatLng source, LatLng destination) async {
    final url = Uri.parse(
      RemoteUrls.mapCoordinate(source.latitude, source.longitude,
          destination.latitude, destination.longitude, Utils.mapKey(context)),
    );
    final response = await http.get(url);

    debugPrint('Response status: ${response.statusCode}');
    debugPrint('Response body: ${response.body}');

    if (response.statusCode == 200) {
      final data = json.decode(response.body);

      if (data['routes'].isEmpty) {
        throw Exception('No routes found');
      }
      final legs = data['routes'][0]['legs'][0];
      final distance = legs['distance']['text'];
      final duration = legs['duration']['text'];

      // Set total distance and duration
      setState(() {
        totalDistance = distance;
        totalDuration = duration; // Get the duration text
      });

      final points = data['routes'][0]['overview_polyline']['points'];
      // final points = data['routes'][0]['overview_polyline']['points'];
      return decodePoly(points);
    } else {
      throw Exception('Failed to load route');
    }
  }

  List<LatLng> decodePoly(String poly) {
    List<LatLng> polylineCoordinates = [];
    int index = 0, len = poly.length;
    int lat = 0, lng = 0;

    while (index < len) {
      int b, shift = 0, result = 0;
      do {
        b = poly.codeUnitAt(index++) - 63;
        result |= (b & 0x1f) << shift;
        shift += 5;
      } while (b >= 0x20);
      int dlat = ((result & 1) != 0 ? ~(result >> 1) : (result >> 1));
      lat += dlat;

      shift = 0;
      result = 0;
      do {
        b = poly.codeUnitAt(index++) - 63;
        result |= (b & 0x1f) << shift;
        shift += 5;
      } while (b >= 0x20);
      int dlng = ((result & 1) != 0 ? ~(result >> 1) : (result >> 1));
      lng += dlng;

      polylineCoordinates.add(LatLng(lat / 1E5, lng / 1E5));
    }

    return polylineCoordinates;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: BlocConsumer<MapCubit, MapStateModel>(
        listener: (context, state) {
          final support = state.status;
          if (support is RefreshStateEveryFive) {
            if (state.isOpenSupport == true) {
              pCubit.deliveryLocation();
            }
          }
        },
        builder: (context, state) {
          LatLng updatedSourceLocation =
              LatLng(state.dLatitude, state.dLongitude);

          // Trigger marker animation when the location changes
          if (updatedSourceLocation != destinationLocation) {
            _animateMarker(updatedSourceLocation);
            destinationLocation = updatedSourceLocation;
            _drawRoute(); // Recalculate route if location changes
          }

          return Stack(
            children: [
              GoogleMap(
                onMapCreated: (controller) {
                  _mapController = controller;
                  _mapController!.animateCamera(
                    CameraUpdate.newLatLng(sourceLocation),
                  );
                },
                initialCameraPosition:
                    CameraPosition(target: sourceLocation, zoom: 14.5),
                markers: _markers,
                polylines: _polyLines,
              ),
              const Positioned(left: 30.0, top: 70.0, child: AppbarLeading()),
              Positioned(
                bottom: 20,
                left: 20,
                child: Container(
                  padding: Utils.symmetric(v: 14.0, h: 10.0),
                  color: Colors.white,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CustomText(
                        text: 'Total Distance: $totalDistance',
                        fontSize: 12.0,
                        fontWeight: FontWeight.w500,
                      ),
                      CustomText(
                        text: 'Estimated Delivery Time: $totalDuration',
                        fontSize: 14.0,
                        fontWeight: FontWeight.w500,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
