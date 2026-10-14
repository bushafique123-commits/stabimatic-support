# Android Physical Device QA

## Device Setup

- Install Android Studio platform-tools.
- Enable Developer Options on the Android device.
- Enable USB debugging.
- Connect the device by USB.
- Accept the RSA authorization prompt on the device.

The project can find ADB from the Android SDK even when `adb` is not available on `PATH`.

## Commands

List connected devices:

```bash
cd mobile-app
npm run android:devices
```

Build and run the QA preflight:

```bash
cd mobile-app
npm run android:sync:dev
npm run android:assemble:debug
npm run android:qa:preflight
```

Install the debug APK:

```bash
cd mobile-app
npm run android:install:debug
```

Capture recent logs:

```bash
cd mobile-app
npm run android:logs
```

Run the full build, preflight, and install path:

```bash
cd mobile-app
npm run android:qa:full
```

## Manual QA Checklist

- App launches without a blank WebView.
- Login works against the selected dev API host.
- Dealer dashboard loads totals.
- Dealer sales list loads.
- Dealer sale create, edit, detail, and delete flows work.
- Installer dashboard loads totals.
- Installer installations list loads.
- Installer installation create, edit, detail, and delete flows work.
- Serial validation returns matched and unmatched states.
- Camera capture opens and returns an image.
- Gallery selection opens and returns an image.
- Uploading an installation image succeeds.
- Notification preferences save.
- Notification inbox loads.
- Notification detail marks an item read.
- Mark-all-read updates unread counts.
- Earnings wallet loads summary totals.
- Earnings and payout filters work.
- Forgot password request, OTP verification, and reset work.
- Mobile number change OTP flow works.
- Email address change OTP flow works.
- Logout clears the local session.

## QA Result Template

```text
Device:
Android version:
Build tested:
API host:
Tester:
Date:

Passed:
Failed:
Blocked:
Notes:
```

## Known Local Blockers

- `npm run android:qa:preflight` fails when no authorized device is connected.
- Physical devices need the API host set to the Windows LAN IP in `environment.android.ts`.
- Release QA remains blocked until production HTTPS URL and signing credentials are configured.
