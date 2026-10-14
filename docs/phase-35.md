# Phase 35 - Android Physical Device QA

## Completed Scope

- Added an Android device QA helper script.
- Added ADB auto-discovery through Android SDK environment variables, local SDK paths, and `PATH`.
- Added npm commands for device listing, preflight, debug APK install, log capture, and full device QA flow.
- Added timeout handling for ADB commands so QA commands do not hang indefinitely.
- Added Android physical device QA documentation.
- Added a manual QA checklist and result template.
- Confirmed ADB exists in the local Android SDK platform-tools folder.
- Confirmed no authorized Android device or emulator is currently connected.

## Commands

```bash
cd mobile-app
npm run android:devices
npm run android:qa:preflight
npm run android:install:debug
npm run android:logs
npm run android:qa:full
```

## Current QA Status

Physical device QA is ready but not executed because no authorized Android device or emulator is connected.

## Deferred To Phase 36

- Store metadata and asset preparation.
- Final brand icon and splash replacement.
- Signed APK/AAB handoff after production URL and keystore are provided.
