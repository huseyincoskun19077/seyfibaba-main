import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../model/banner_model.dart';
import '../../model/home_model.dart';
import '../repository/home_repository.dart';

part 'home_controller_state.dart';

class HomeControllerCubit extends Cubit<HomeControllerState> {
  HomeControllerCubit(HomeRepository homeRepository)
      : _homeRepository = homeRepository,
        super(HomeControllerLoading()) {
    getHomeData();
  }

  final HomeRepository _homeRepository;
  HomeModel? homeModel;
  List<BannerModel> sliderBanner = [];

  Future<void> getHomeData() async {
    emit(HomeControllerLoading());

    final result = await _homeRepository.getHomeData();
    result.fold(
      (failuer) {
        emit(HomeControllerError(errorMessage: failuer.message));
      },
      (data) {
        homeModel = data;
        storeBannerImage();
        emit(HomeControllerLoaded(homeModel: data));
      },
    );
  }

  void storeBannerImage() {
    if (sliderBanner.isNotEmpty) {
      sliderBanner.clear();
    }
    if (homeModel != null && homeModel!.sliderBannerOne != null) {
      //print('storeBannerImage1 ${homeModel!.sliderBannerTwo!.image}');
      sliderBanner.add(homeModel!.sliderBannerOne!);
    }
    if (homeModel != null && homeModel!.sliderBannerTwo != null) {
      //print('storeBannerImage2 ${homeModel!.sliderBannerTwo!.image}');
      sliderBanner.add(homeModel!.sliderBannerTwo!);
    }
  }
}
