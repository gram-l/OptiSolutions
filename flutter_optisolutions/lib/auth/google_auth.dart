import 'package:google_sign_in/google_sign_in.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_optisolutions/config/api_config.dart';

class GoogleAuthService {
  static const String baseUrl = ApiConfig.baseUrl;

  final GoogleSignIn _googleSignIn = GoogleSignIn(
    serverClientId: '1041975122502-tjr2cth2cnetpo53o75l4r6gu99tr63g.apps.googleusercontent.com',
    scopes: ['email', 'profile'],
  );

Future<Map<String, dynamic>?> signInWithGoogle() async {
  try {
    await _googleSignIn.signOut();

    final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
    if (googleUser == null) return null; // user cancelled

    final GoogleSignInAuthentication googleAuth = await googleUser.authentication;
    final String? idToken = googleAuth.idToken;

    if (idToken == null) {
      throw Exception('Failed to get ID token from Google');
    }

    final response = await http.post(
      Uri.parse('$baseUrl/auth/google'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'id_token': idToken}),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 200) {
      return data;
    } else if (response.statusCode == 404) {
      // Matches GoogleAuthController's "no account found" response
      throw Exception(data['message'] ?? 'This Google account is not registered in the system.');
    } else if (response.statusCode == 401) {
      throw Exception(data['message'] ?? 'Invalid Google sign-in. Please try again.');
    } else {
      throw Exception(data['message'] ?? 'Google sign-in failed. Please try again.');
    }
  } on FormatException {
    // Response wasn't valid JSON at all (e.g. Laravel threw an HTML error page)
    throw Exception('Unexpected response from server.');
  } catch (e) {
    print('Google Sign-In error: $e');
    rethrow;
  }
}

  Future<void> signOut() async {
    await _googleSignIn.signOut();
  }
}