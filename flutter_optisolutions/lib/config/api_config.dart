class ApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://10.235.3.34:8000/api',
  );
}

// sample flutter run --dart-define=API_URL=http://10.145.123.34:8000/api