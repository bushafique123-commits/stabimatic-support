# Phase 24 - Change Mobile And Email

## Completed Scope

- Added `ContactChangeService`.
- Added API routes for:
  - `POST /api/v1/profile/change-mobile/send-otp`
  - `POST /api/v1/profile/change-mobile/verify`
  - `POST /api/v1/profile/change-email/send-otp`
  - `POST /api/v1/profile/change-email/verify`
- Protected contact change routes with JWT authentication.
- Added duplicate mobile and email validation.
- Added mobile and email OTP delivery to the new contact value.
- Added resend wait enforcement.
- Added contact change OTP throttling.
- Added verification attempt limits.
- Added OTP expiry checks.
- Updated the verified mobile or email after successful OTP verification.
- Preserved account verification when the remaining contact channel is already verified.
- Logged mobile and email change activity to `activity_logs`.
- Added mobile profile service methods for contact changes.
- Added Ionic profile controls for sending and verifying mobile/email change OTPs.
- Added resend countdowns and inline validation for both contact change forms.

## API Routes

### Send Mobile Change OTP

`POST /api/v1/profile/change-mobile/send-otp`

Fields:

- `mobile`

### Verify Mobile Change OTP

`POST /api/v1/profile/change-mobile/verify`

Fields:

- `mobile`
- `otp`

### Send Email Change OTP

`POST /api/v1/profile/change-email/send-otp`

Fields:

- `email`

### Verify Email Change OTP

`POST /api/v1/profile/change-email/verify`

Fields:

- `email`
- `otp`

## Deferred To Phase 25

- Push notification foundation.
- Device token registration.
- Notification preferences.
