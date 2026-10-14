# Phase 31 - Android Build Preparation

## Completed Scope

- Added the native Capacitor Android project under `mobile-app/android`.
- Confirmed Android package namespace and application ID as `com.stabimatic.app`.
- Confirmed Android app label as `STABIMATIC`.
- Kept Capacitor web output mapped to `www`.
- Synced existing Capacitor plugins into the Android platform:
  - App
  - Camera
  - Device
  - Network
  - Preferences
- Added Android manifest permissions for API access, network checks, camera capture, gallery selection, and Android 13+ notifications.
- Confirmed generated launcher and splash resources are present and documented replacement paths for final brand artwork.
- Added npm scripts for Android add, sync, open, debug run, debug APK build, and release APK build.
- Added Android build documentation with prerequisites, sync steps, Android Studio workflow, release commands, and release checklist.

## Android App Details

- App ID: `com.stabimatic.app`
- App Name: `STABIMATIC`
- Minimum SDK: 22
- Target SDK: 34
- Web Directory: `www`

## Build Commands

```bash
cd mobile-app
npm run build
npm run android:sync
npm run android:assemble:debug
```

## Release Gate

`mobile-app/src/environments/environment.prod.ts` still contains a placeholder production API URL. Replace it with the deployed HTTPS API URL before producing a signed release build.

## Deferred To Phase 32

- Android development readiness.
- Android emulator and physical-device API configuration.
- Android debug build workflow.
