# Android Development Guide

## Local API Target

The Android development build uses:

```text
http://10.0.2.2/stabimatic/api/v1
```

`10.0.2.2` is the Android emulator route back to the Windows host machine. Keep Apache/XAMPP running locally with the PHP API available at `/stabimatic/api/v1`.

For a physical Android device, replace `apiUrl` in `mobile-app/src/environments/environment.android.ts` with the Windows machine LAN IP address, for example:

```text
http://192.168.1.25/stabimatic/api/v1
```

## Debug HTTP

Android debug builds allow cleartext HTTP through:

```text
mobile-app/android/app/src/debug/AndroidManifest.xml
mobile-app/android/app/src/debug/res/xml/debug_network_security_config.xml
```

This is limited to the debug source set. Release builds should continue to use HTTPS through `environment.prod.ts`.

## Daily Development Loop

```bash
cd mobile-app
npm run android:sync:dev
npm run android:run:dev
```

If Android Studio is preferred:

```bash
cd mobile-app
npm run android:sync:dev
npm run android:open
```

Then run the app from Android Studio on an emulator or physical device.

## Debug APK

```bash
cd mobile-app
npm run android:sync:dev
npm run android:assemble:debug
```

Debug APK output:

```text
mobile-app/android/app/build/outputs/apk/debug/app-debug.apk
```

## Device Smoke Test

- App launches to the auth flow.
- Login works against the dev API.
- Dealer sales list loads.
- Dealer sale create/edit works.
- Installer dashboard loads.
- Installer serial check works.
- Camera/gallery installation image flow opens.
- Notification inbox opens.
- Earnings wallet opens.

## Troubleshooting

- If the emulator cannot reach the API, confirm Apache is running and use `10.0.2.2`.
- If a physical device cannot reach the API, use the host LAN IP and keep phone and computer on the same network.
- If HTTP is blocked, confirm you are using a debug build, not a release build.
- If stale web content appears, run `npm run android:sync:dev` again.
