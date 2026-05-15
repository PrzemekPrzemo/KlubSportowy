import 'member.dart';

/// Stan autoryzacji aplikacji. Sealed — go_router uzupełnia routing
/// na podstawie typu.
sealed class AuthState {
  const AuthState();
}

/// Trwa restore z secure storage / pingowanie /me.
class AuthLoading extends AuthState {
  const AuthLoading();
}

/// Brak tokenu / 401 / explicit logout.
class Unauthenticated extends AuthState {
  const Unauthenticated({this.message});
  final String? message;
}

/// Login się udał, ale user ma >1 klub — musi wybrać.
class MultiClubChoice extends AuthState {
  const MultiClubChoice({required this.tempToken, required this.clubs});
  final String tempToken;
  final List<Club> clubs;
}

/// Zalogowany z konkretnym klubem.
class Authenticated extends AuthState {
  const Authenticated({required this.member});
  final Member member;
}
