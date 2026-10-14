# Android Release Candidate Guide

## Release Guard

Run the release guard before producing a release candidate:

```bash
cd mobile-app
npm run release:check
```

The guard fails if `mobile-app/src/environments/environment.prod.ts` still points to a placeholder, local, emulator, LAN, or non-HTTPS API URL.

## Production API

Set the production API URL in:

```text
mobile-app/src/environments/environment.prod.ts
```

The value must be the deployed HTTPS API endpoint.

You can update it with:

```bash
cd mobile-app
npm run release:set-api -- https://api.example.com/stabimatic/api/v1
```

## Signing Setup

Create a release keystore outside source control, then copy:

```text
mobile-app/android/keystore.properties.example
```

to:

```text
mobile-app/android/keystore.properties
```

Update the copied file with the real keystore path, store password, key alias, and key password. `keystore.properties`, `.jks`, and `.keystore` files are ignored by the Android project.

## Release Candidate Build

After the production API URL and signing credentials are configured:

```bash
cd mobile-app
npm run android:release:candidate
```

For Play Store handoff, build the Android App Bundle:

```bash
cd mobile-app
npm run android:bundle:release
```

Release APK output:

```text
mobile-app/android/app/build/outputs/apk/release/
```

Release AAB output:

```text
mobile-app/android/app/build/outputs/bundle/release/
```

## QA Matrix

- Fresh install opens the app without WebView errors.
- Login succeeds for a verified Dealer.
- Login succeeds for a verified Installer.
- Invalid login errors are visible and do not crash the app.
- Dealer dashboard totals load.
- Dealer sales list, create, edit, detail, and delete flows work.
- Installer dashboard totals load.
- Installer installation list, create, edit, detail, and delete flows work.
- Serial validation reports matched and unmatched states correctly.
- Camera capture opens from the installation form.
- Gallery selection opens from the installation form.
- Notification preferences save.
- Notification inbox loads, detail marks read, and mark-all-read works.
- Earnings wallet summary, earnings history, and payout history load.
- Forgot password request, OTP verification, and reset work.
- Mobile/email change OTP flow works.
- Logout clears the local session.

## Release Blockers

- Do not ship while `environment.prod.ts` contains `domain.com`.
- Do not ship unsigned APKs.
- Do not ship with generated placeholder app icons or splash images.
- Do not ship until SMS, SMTP, and notification worker settings are configured in the deployed API environment.
