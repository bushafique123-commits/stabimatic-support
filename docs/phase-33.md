# Phase 33 - Android QA And Release Candidate

## Completed Scope

- Added a mobile release configuration guard.
- Added npm release scripts that block placeholder production API URLs.
- Added optional Android release signing support through ignored `keystore.properties`.
- Added a keystore properties example file.
- Updated Android `.gitignore` to exclude keystore files and signing properties.
- Added Android release candidate documentation.
- Added Android QA matrix for app launch, auth, Dealer, Installer, serial, camera/gallery, notifications, earnings, password reset, contact change, and logout flows.
- Verified the Android development APK still builds after release-prep changes.

## Release Commands

```bash
cd mobile-app
npm run release:check
npm run android:release:candidate
```

## Current Release Gate

`mobile-app/src/environments/environment.prod.ts` still contains a placeholder production API URL. The release guard intentionally fails until that value is replaced with the deployed HTTPS API endpoint.

## Deferred To Phase 34

- Deployed production API URL configuration.
- Signed Android release build.
- Final production deployment checklist.
