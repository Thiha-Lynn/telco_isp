import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../data/profile_repository.dart';
import '../../data/models/bind_user_model.dart';
import '../../../../core/api/api_service.dart';

// Events
abstract class ProfileEvent extends Equatable {
  const ProfileEvent();

  @override
  List<Object?> get props => [];
}

class ProfileLoadRequested extends ProfileEvent {}

class ProfileRefreshRequested extends ProfileEvent {}

// States
abstract class ProfileState extends Equatable {
  const ProfileState();

  @override
  List<Object?> get props => [];
}

class ProfileInitial extends ProfileState {}

class ProfileLoading extends ProfileState {}

class ProfileLoaded extends ProfileState {
  final List<BindUser> bindUsers;
  final BindUser? primaryBindUser;

  const ProfileLoaded({
    required this.bindUsers,
    this.primaryBindUser,
  });

  @override
  List<Object?> get props => [bindUsers, primaryBindUser];
}

class ProfileError extends ProfileState {
  final String message;

  const ProfileError({required this.message});

  @override
  List<Object?> get props => [message];
}

// BLoC
class ProfileBloc extends Bloc<ProfileEvent, ProfileState> {
  final ProfileRepository _profileRepository;

  ProfileBloc({ProfileRepository? profileRepository})
      : _profileRepository = profileRepository ?? ProfileRepository(),
        super(ProfileInitial()) {
    on<ProfileLoadRequested>(_onProfileLoadRequested);
    on<ProfileRefreshRequested>(_onProfileRefreshRequested);
  }

  Future<void> _onProfileLoadRequested(
    ProfileLoadRequested event,
    Emitter<ProfileState> emit,
  ) async {
    emit(ProfileLoading());
    await _loadProfile(emit);
  }

  Future<void> _onProfileRefreshRequested(
    ProfileRefreshRequested event,
    Emitter<ProfileState> emit,
  ) async {
    await _loadProfile(emit);
  }

  Future<void> _loadProfile(Emitter<ProfileState> emit) async {
    try {
      final bindUsers = await _profileRepository.getBindUsers();
      
      // Get the first (primary) bind user with details if available
      BindUser? primaryBindUser;
      if (bindUsers.isNotEmpty) {
        primaryBindUser = await _profileRepository.getBindUserDetails(bindUsers.first.id);
        primaryBindUser ??= bindUsers.first;
      }

      emit(ProfileLoaded(
        bindUsers: bindUsers,
        primaryBindUser: primaryBindUser,
      ));
    } on ApiException catch (e) {
      emit(ProfileError(message: e.message));
    } catch (e) {
      emit(ProfileError(message: 'Failed to load profile data'));
    }
  }
}
