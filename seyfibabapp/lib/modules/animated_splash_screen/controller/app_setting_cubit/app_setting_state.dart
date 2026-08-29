part of 'app_setting_cubit.dart';

abstract class AppSettingState extends Equatable {
  const AppSettingState();

  @override
  List<Object> get props => [];
}

class AppSettingStateInitial extends AppSettingState {
  const AppSettingStateInitial();
}

class AppSettingStateLoading extends AppSettingState {
  const AppSettingStateLoading();
}

class AppSettingStateError extends AppSettingState {
  final String meg;
  final int statusCode;
  const AppSettingStateError(this.meg, this.statusCode);

  @override
  List<Object> get props => [meg, statusCode];
}

class AppSettingStateNotCashed extends AppSettingState {
  final String meg;

  const AppSettingStateNotCashed(this.meg);

  @override
  List<Object> get props => [meg];
}

class AppSettingStateLoaded extends AppSettingState {
  final WebsiteSetupModel settingModel;
  const AppSettingStateLoaded(this.settingModel);

  @override
  List<Object> get props => [settingModel];
}

class AppSettingStateLocalLoaded extends AppSettingState {
  final SettingModel settingModel;
  const AppSettingStateLocalLoaded(this.settingModel);

  @override
  List<Object> get props => [settingModel];
}
