# ClubDesk Mobile (Flutter MVP)

Mobile portal zawodnika/klubowicza dla platformy ClubDesk.

> **Status:** MVP. Działa w trybie mock (hardcoded JSON) — backend REST API
> dołączany jest osobno (`/api/mobile/v1/*`). Po podłączeniu backendu wystarczy
> zbudować z `--dart-define=USE_MOCK=false`.

---

## Wymagania

- **Flutter SDK** ≥ 3.27 (Dart ≥ 3.5)
- **Android Studio** lub **Xcode** (do emulatora/buildowania)
- **Konto Firebase** (push notifications) — opcjonalne dla developmentu

## Pierwsze uruchomienie

```bash
cd flutter_app

# Wygeneruj platformowe pliki (android/, ios/, windows/, ...)
# To jest jednorazowe — Flutter dotworzy folder android/ i ios/ z konfiguracją:
flutter create . --platforms=android,ios --org=pl.clubdesk

# Pobierz zależności
flutter pub get

# Sprawdź czy wszystko gra
flutter analyze

# Uruchom (mock data, działa bez backendu)
flutter run
```

> Po `flutter create .` zaktualizuj nowo wygenerowany
> `android/app/build.gradle.kts` o ustawienia z
> `android/app/build.gradle.kts.example` (głównie `minSdk = 21`,
> `applicationId = "pl.clubdesk.mobile"`, plugin `google-services`).
>
> Podobnie dla iOS — `ios/Runner/Info.plist` uzupełnij wg
> `ios/Runner/Info.plist.example`.

## Tryb mock vs real API

Cały data layer wspiera flagę `USE_MOCK` (kompilowaną z `--dart-define`):

```bash
# Mock (domyślnie, do developmentu UI)
flutter run

# Prawdziwy backend (staging)
flutter run --dart-define=USE_MOCK=false \
            --dart-define=API_BASE=https://staging.clubdesk.pl/api/mobile/v1

# Produkcja
flutter run --dart-define=USE_MOCK=false
# (domyślny API_BASE = https://portal.clubdesk.pl/api/mobile/v1)
```

W trybie mock:
- każdy login z dowolnym hasłem (>= 8 znaków) zadziała
- `email = fail@test.com` zwraca błąd autoryzacji (testowanie error UI)
- dane są hardcoded w `lib/features/*/data/*_api.dart`

## Firebase / push notifications

Push **wymaga plików konfiguracyjnych Firebase** (per-projekt):

1. Utwórz projekt w [Firebase Console](https://console.firebase.google.com/).
2. Dodaj app Android (bundle: `pl.clubdesk.mobile`) → pobierz `google-services.json`
   → zapisz w `android/app/google-services.json`.
3. Dodaj app iOS (bundle: `pl.clubdesk.mobile`) → pobierz
   `GoogleService-Info.plist` → zapisz w `ios/Runner/GoogleService-Info.plist`.
4. W `android/app/build.gradle.kts` odkomentuj
   `id("com.google.gms.google-services")`.
5. W `lib/core/firebase_bootstrap.dart` odkomentuj inicjalizację
   `Firebase.initializeApp(...)`.
6. Wygeneruj `firebase_options.dart` przez `flutterfire configure`.

Bez tych plików app działa, ale push nie zarejestruje się (graceful no-op).

## Build

```bash
# Android APK (release, mock data)
flutter build apk --release

# Android APK z prawdziwym API
flutter build apk --release --dart-define=USE_MOCK=false

# iOS (wymaga macOS + Xcode + konfiguracji signing)
flutter build ios --release --dart-define=USE_MOCK=false
```

## Architektura

```
lib/
├── main.dart                  # bootstrap (Firebase, runApp)
├── app.dart                   # MaterialApp.router + theme + locale
├── config/                    # ApiConfig (base URL, mock flag), theme
├── core/                      # ApiClient (Dio), SecureStorage, Result, exceptions
├── features/                  # feature-first, każdy ma data/ domain/ ui/
│   ├── auth/
│   ├── dashboard/
│   ├── trainings/
│   ├── fees/
│   ├── events/
│   ├── profile/
│   ├── notifications/
│   └── settings/
├── shared/widgets/            # AsyncValueView, ErrorView, LoadingView, EmptyView
├── localization/              # JSON-based l10n (pl, en)
└── routing/                   # go_router config + ShellRoute z bottom nav
```

**State management:** Riverpod (NotifierProvider dla persistent state,
FutureProvider.autoDispose dla async data).

**Networking:** Dio z interceptors:
- automatyczna injekcja `Authorization: Bearer <token>`
- 401 → próba refresh raz, potem logout
- mapowanie błędów na typed `AppException` (Auth/Validation/Network/Api)

**Routing:** go_router z `redirect` opartym o `AuthState` (sealed class).

**Storage:** `flutter_secure_storage` (tokeny w keychainie),
`shared_preferences` (theme/locale/push enabled).

## Ekrany MVP

| Ekran | Status | Notatki |
|-------|--------|---------|
| Login | ✅ | Email/password + walidacja + forgot password link |
| Forgot password | ✅ | Email + send |
| Select club | ✅ | Multi-club identity (lista klubów po loginie) |
| Dashboard | ✅ | Dzisiaj/Składki/Powiadomienia/Statystyki + pull-to-refresh |
| Trainings list | ✅ | Grouped by date + RSVP badge |
| Training detail | ✅ | Lokalizacja, trener, RSVP (Będę/Nie wiem/Nie będę) |
| Fees list | ✅ | Tabs: Zaległe / Do zapłaty / Opłacone |
| Fee detail | ✅ | "Zapłać" → external browser z Stripe/P24 |
| Profile | ✅ | Avatar, dane, link do edycji |
| Edit profile | ✅ | Telefon, adres → PATCH /me |
| Notifications | ✅ | Lista + swipe to dismiss + mark read on tap |
| Settings | ✅ | Theme (system/light/dark), język (PL/EN), push toggle, logout |

## TODO po MVP

- Podłączenie Firebase (po dostarczeniu konfigów per-projekt klubu)
- `firebase_messaging` foreground banner + onMessageOpenedApp deep links
- `POST /push/register` po loginie (rejestracja FCM tokenu)
- Avatar upload w edit profile
- Tłumaczenia EN dla wszystkich screen (część kluczy nadal w JSON tylko PL inline)
- Testy widget/integration

## Migracja do osobnego repo (`ClubDesk-mobile`)

Aplikacja jest self-contained — wystarczy:

```bash
# z root KlubSportowy
git mv flutter_app/ ../ClubDesk-mobile/
cd ../ClubDesk-mobile
git init
git add .
git commit -m "Initial commit — extracted from KlubSportowy"
```

W docelowym repo:
- skopiuj `.gitignore` Flutter (już jest w folderze)
- skonfiguruj CI (build APK + analyze)
- dodaj Fastlane do iOS/Android release
