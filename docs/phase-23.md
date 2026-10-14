# Phase 23 - Forgot Password

## Completed Scope

- Added `PasswordResetService`.
- Added forgot password methods to `AuthController`.
- Added API routes for:
  - `POST /api/v1/auth/forgot-password`
  - `POST /api/v1/auth/verify-reset-otp`
  - `POST /api/v1/auth/reset-password`
- Added reset OTP delivery by identifier:
  - email identifier sends email OTP
  - mobile identifier sends mobile OTP
- Added reset request throttling.
- Added resend wait enforcement.
- Added reset OTP verification attempt limits.
- Added OTP expiry checks.
- Added short-lived password reset token after OTP verification.
- Tied reset tokens to verified OTP rows.
- Consumed the verified OTP row after password reset.
- Revoked active mobile JWT sessions after password reset.
- Stored reset passwords with `password_hash()`.
- Logged reset OTP send, reset OTP verification, and password reset activity.
- Added mobile AuthService forgot/reset methods.
- Replaced the Ionic forgot password placeholder with a three-step reset flow.

## API Routes

### Forgot Password

`POST /api/v1/auth/forgot-password`

Fields:

- `identifier`

The identifier may be a registered mobile number or email address.

### Verify Reset OTP

`POST /api/v1/auth/verify-reset-otp`

Fields:

- `user_id`
- `channel`
- `otp`

Returns a short-lived `reset_token`.

### Reset Password

`POST /api/v1/auth/reset-password`

Fields:

- `reset_token`
- `password`
- `confirm_password`

## Deferred To Phase 24

- OTP-protected mobile number change.
- OTP-protected email address change.
