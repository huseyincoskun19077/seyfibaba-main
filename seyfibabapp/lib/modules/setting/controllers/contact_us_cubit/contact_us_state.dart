part of 'contact_us_cubit.dart';

abstract class ContactUsState extends Equatable {
  const ContactUsState();

  @override
  List<Object> get props => [];
}

class ContactUsStateLoading extends ContactUsState {}

class ContactUsStateLoaded extends ContactUsState {
  final ContactModel contactModel;
  const ContactUsStateLoaded(this.contactModel);

  @override
  List<Object> get props => [contactModel];
}

class ContactUsStateError extends ContactUsState {
  final String errorMessage;
  const ContactUsStateError({
    required this.errorMessage,
  });

  @override
  List<Object> get props => [errorMessage];
}
