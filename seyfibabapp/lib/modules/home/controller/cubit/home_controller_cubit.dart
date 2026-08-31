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
    sliderBanner.clear();
    if (homeModel == null) return;

    if (homeModel!.mobileSliders.isNotEmpty) {
      for (final slider in homeModel!.mobileSliders) {
        sliderBanner.add(
          BannerModel(
            id: slider.id,
            link: slider.link,
            image: slider.image,
            productSlug: slider.productSlug,
            bannerLocation: '',
            slug: slider.productSlug,
            titleOne: slider.title,
            titleTwo: slider.subtitle,
            status: slider.status ? 1 : 0,
            badge: '',
          ),
        );
      }
      return;
    }

    if (homeModel!.sliderBannerOne != null) {
      sliderBanner.add(homeModel!.sliderBannerOne!);
    }
    if (homeModel!.sliderBannerTwo != null) {
      sliderBanner.add(homeModel!.sliderBannerTwo!);
    }

    if (sliderBanner.isEmpty && homeModel!.sliders.isNotEmpty) {
      for (final slider in homeModel!.sliders) {
        sliderBanner.add(
          BannerModel(
            id: slider.id,
            link: '',
            image: slider.image,
            productSlug: slider.productSlug,
            bannerLocation: slider.sliderLocation,
            slug: slider.productSlug,
            titleOne: slider.titleOne,
            titleTwo: slider.titleTwo,
            status: slider.status,
            badge: slider.badge,
          ),
        );
      }
    }
  }
}
