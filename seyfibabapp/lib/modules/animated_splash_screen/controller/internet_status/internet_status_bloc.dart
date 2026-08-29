import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

part 'internet_status_event.dart';
part 'internet_status_state.dart';

class InternetStatusBloc extends Bloc<InternetStatusEvent, InternetStatusState> {
  final Connectivity _connectivity = Connectivity();
  late StreamSubscription<List<ConnectivityResult>> _subscription;

  InternetStatusBloc() : super(InternetStatusInitial()) {
    on<InternetStatusBackEvent>((event, emit) =>
        emit(const InternetStatusBackState('Your internet was restored')));
    on<InternetStatusLostEvent>((event, emit) =>
        emit(const InternetStatusLostState('No internet connection')));

    _initialCheck();

    _subscription = Connectivity()
        .onConnectivityChanged
        .listen((List<ConnectivityResult> result) {
      if (result.contains(ConnectivityResult.mobile) ||
          result.contains(ConnectivityResult.wifi) ||
          result.contains(ConnectivityResult.ethernet)) {
        // debugPrint('called - mobile/wifi');
        add(InternetStatusBackEvent());
      } else {
        // debugPrint('called - nothing');
        add(InternetStatusLostEvent());
      }
    });
  }

  Future<void> _initialCheck() async {
    // debugPrint('called _initialCheck');
    final connectivityResult = await _connectivity.checkConnectivity();
    if (connectivityResult.contains(ConnectivityResult.none)) {
      // debugPrint('called - lost');
      add(InternetStatusLostEvent());
    } else {
      // debugPrint('called - gained');
      add(InternetStatusBackEvent());
    }
  }

  @override
  Future<void> close() {
    _subscription.cancel();
    return super.close();
  }
}
