# Phase 32 - Android Development Readiness

## Completed Scope

- Skipped iOS build preparation for now.
- Removed the generated iOS platform folder from the active workspace.
- Added an Android-specific Angular environment file for emulator development.
- Added an Angular `android` build configuration.
- Added an Angular serve configuration for the Android environment.
- Added npm scripts for Android development sync and device/emulator run.
- Added debug-only Android cleartext HTTP support for local API development.
- Kept production/release API behavior pointed at the production environment file.
- Added Android development workflow documentation.
- Added Android device smoke-test checklist.

## Android Development API

Default Android emulator API target:

```text
http://10.0.2.2/stabimatic/api/v1
```

For physical device testing, update:

```text
mobile-app/src/environments/environment.android.ts
```

Use the Windows host LAN IP address while the phone and computer are on the same network.

## Development Commands

```bash
cd mobile-app
npm run android:sync:dev
npm run android:run:dev
```

## Debug APK Command

```bash
cd mobile-app
npm run android:sync:dev
npm run android:assemble:debug
```

## Deferred To Phase 33

- Android end-to-end QA.
- Production API URL replacement.
- Signed release build preparation.
- Release candidate checklist.
