# Android Store Assets And Handoff

## Store Files

```text
mobile-app/store/android/metadata/en-US/listing.md
mobile-app/store/android/metadata/en-US/data-safety.md
mobile-app/store/android/handoff-checklist.md
mobile-app/store/android/qa-result-template.md
mobile-app/store/android/release-notes/v1.0.0.txt
mobile-app/store/android/screenshots/
```

## Readiness Check

```bash
cd mobile-app
npm run android:store:check
```

The check confirms local store templates, app identity, launcher/splash resources, APK outputs, production API readiness, and Android signing readiness.

## Graphic Asset Notes

Prepare store graphics from final approved STABIMATIC artwork. Current Google Play guidance requires a feature graphic at `1024 x 500` and asks app screenshots to show the real in-app experience. Use consistent visual style across icon, feature graphic, screenshots, and the app UI.

## Screenshot Plan

Capture real device screenshots for:

- Login screen.
- Dealer dashboard.
- Dealer sales list.
- Installer dashboard.
- Installation form with serial validation.
- Notification inbox.
- Earnings wallet.

## Current Handoff Status

Blocked until:

- Production HTTPS API URL is supplied.
- Android signing keystore is configured.
- Final launcher icon and splash artwork are supplied.
- Physical-device QA is completed.
