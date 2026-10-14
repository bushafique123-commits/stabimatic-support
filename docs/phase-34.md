# Phase 34 - Production Android Release

## Completed Scope

- Added a production API URL update script.
- Added Android signing configuration validation.
- Updated release npm scripts so production release commands require:
  - Deployed HTTPS production API URL.
  - Valid Android signing properties.
  - Existing keystore file.
- Added an explicit unsigned release packaging script for non-shipping smoke builds.
- Added an Android App Bundle release command for Play Store handoff.
- Kept signed release credentials out of source control.
- Verified release gates fail cleanly while production values are not configured.
- Verified unsigned release packaging still works for build smoke testing.

## Production API Command

```bash
cd mobile-app
npm run release:set-api -- https://api.example.com/stabimatic/api/v1
```

The command rejects HTTP, placeholder, localhost, emulator, and LAN URLs.

## Signing Command

```bash
cd mobile-app
npm run android:signing:check
```

The check requires:

- `mobile-app/android/keystore.properties`
- `storeFile`
- `storePassword`
- `keyAlias`
- `keyPassword`
- An existing keystore file

## Production Release Commands

```bash
cd mobile-app
npm run android:release:candidate
npm run android:bundle:release
```

## Non-Shipping Smoke Build

```bash
cd mobile-app
npm run android:assemble:unsigned
```

Unsigned APKs are for packaging validation only and must not be distributed as production builds.

## Current Blockers

- Production API URL is still not configured.
- Android signing keystore is still not configured.
- Final brand launcher and splash artwork is still not supplied.

## Deferred To Phase 35

- Android physical device QA.
- Production API deployment smoke tests.
- Store asset preparation.
- Final signed APK/AAB handoff.
