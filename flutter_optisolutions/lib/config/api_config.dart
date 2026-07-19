class ApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://192.168.1.11:8000/api',
  
  );
}
// 192.168.254.147 home
// 192.168.43.16 noemi
//192.168.193.172 lipa bsu
// sample flutter run --dart-define=API_URL=http://10.145.123.34:8000/api //samsungq