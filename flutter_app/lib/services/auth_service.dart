import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';
import '../models/member.dart';
import 'api_client.dart';

class AuthService extends ChangeNotifier {
  Member? _member;
  Club? _club;
  bool _isLoading = false;

  Member? get member => _member;
  Club? get club => _club;
  bool get isAuthenticated => _member != null;
  bool get isLoading => _isLoading;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final memberJson = prefs.getString('member_data');
    final clubJson = prefs.getString('club_data');
    if (memberJson != null) {
      _member = Member.fromJson(jsonDecode(memberJson));
    }
    if (clubJson != null) {
      _club = Club.fromJson(jsonDecode(clubJson));
    }
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await ApiClient.post(
        ApiConfig.authLoginUrl,
        body: {'email': email, 'password': password},
      );

      _member = Member.fromJson(response['member']);
      _club = Club.fromJson(response['club']);

      final token = response['token'] as String;
      await ApiClient.setToken(token);

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('member_data', jsonEncode(response['member']));
      await prefs.setString('club_data', jsonEncode(response['club']));

      _isLoading = false;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      _isLoading = false;
      notifyListeners();
      throw e;
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      rethrow;
    }
  }

  Future<void> logout() async {
    _member = null;
    _club = null;
    await ApiClient.clearToken();
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('member_data');
    await prefs.remove('club_data');
    notifyListeners();
  }
}
