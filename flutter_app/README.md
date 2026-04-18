# ClubDesk — Flutter App

Mobilny portal zawodnika dla platformy ClubDesk.

## Wymagania

- Flutter SDK >= 3.2.0
- Dart >= 3.2.0
- Android Studio / Xcode (dla emulatora)

## Instalacja

```bash
cd flutter_app
flutter pub get
```

## Konfiguracja

Edytuj `lib/config/api_config.dart` i ustaw `baseUrl` na adres serwera:

```dart
static const String baseUrl = 'https://twoja-instancja.clubdesk.pl';
```

## Uruchomienie

```bash
# Android
flutter run

# iOS
flutter run --device-id=<ios-device>

# Web (dev)
flutter run -d chrome
```

## Build

```bash
# APK (Android)
flutter build apk --release

# iOS
flutter build ios --release

# Web
flutter build web
```

## Ekrany

- **Login** — logowanie email + hasło (portal zawodnika)
- **Dashboard** — witaj + statystyki (wpłaty, nadchodzące wydarzenia)
- **Wydarzenia** — lista nadchodzących z pull-to-refresh
- **Składki** — historia wpłat z filtrem roku + suma
- **Profil** — dane osobowe + wylogowanie

## Architektura

- **Provider** do zarządzania stanem (AuthService)
- **ApiClient** z Bearer token w secure storage
- **Material 3** z dynamic color scheme
- **Dark mode** automatyczny (system preference)

## API

App komunikuje się z backendem przez REST API v1:
- `POST /api/v1/auth/login` — login (email + password)
- `GET /api/v1/events/upcoming` — nadchodzące wydarzenia
- `GET /api/v1/payments` — historia wpłat
- `GET /api/v1/payments/summary` — suma wpływów roku
