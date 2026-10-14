# Android Build Guide

## Prerequisites

- Android Studio with Android SDK installed.
- JDK 17 available on `PATH`.
- Node.js LTS and npm.
- Capacitor dependencies installed from `mobile-app/package-lock.json`.

## Configuration

- Android application ID: `com.stabimatic.app`.
- Android app name: `STABIMATIC`.
- Minimum SDK: 22.
- Target SDK: 34.
- Production API URL lives in `mobile-app/src/environments/environment.prod.ts`.

Before a release build, replace the placeholder production API URL with the deployed HTTPS API endpoint.

## Permissions

The Android manifest is configured for:

- Internet access for REST API calls.
- Network state access for connectivity checks.
- Camera access for installation image capture.
- Image gallery access on Android 13+ through `READ_MEDIA_IMAGES`.
- Image gallery access on Android 12 and below through `READ_EXTERNAL_STORAGE`.
- Notification permission for Android 13+ notification delivery.

## Icons And Splash

Capacitor generated Android launcher and splash resources during platform creation. Replace these files with approved STABIMATIC artwork before store submission:

```text
mobile-app/android/app/src/main/res/mipmap-*/ic_launcher*.png
mobile-app/android/app/src/main/res/drawable*/splash.png
```

Keep the resource names unchanged so the manifest continues to resolve `@mipmap/ic_launcher`, `@mipmap/ic_launcher_round`, and the launch theme splash image.

## Local Sync

```bash
cd mobile-app
npm install
npm run build
npx cap sync android
```

The same sync workflow is available through:

```bash
npm run android:sync
```

## Android Studio

Open the native project with:

```bash
cd mobile-app
npm run android:open
```

Use Android Studio to install SDK components, select a device or emulator, and run the app.

## Debug APK

```bash
cd mobile-app
npm run android:assemble:debug
```

The debug APK is generated under:

```text
mobile-app/android/app/build/outputs/apk/debug/
```

## Release APK

Create a release keystore and configure signing in Android Studio before publishing. After signing is configured:

```bash
cd mobile-app
npm run android:assemble:release
```

The release APK is generated under:

```text
mobile-app/android/app/build/outputs/apk/release/
```

## Release Checklist

- Set `environment.prod.ts` to the deployed HTTPS API endpoint.
- Replace generated launcher and splash assets with approved brand artwork.
- Run `npm run build`.
- Run `npm run android:sync`.
- Confirm camera/gallery flows on a physical Android device.
- Confirm login, sales, installations, notifications, and earnings against the production API.
- Configure signing credentials outside source control.
- Build and archive the signed release artifact.
