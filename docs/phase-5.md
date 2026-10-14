# Phase 5 - Email OTP Verification

## Completed Scope

- Implemented `POST /api/v1/auth/send-email-otp`.
- Implemented `POST /api/v1/auth/verify-email-otp`.
- Added `EmailOtpService` for email OTP resend and verification business logic.
- Added SMTP sending support in `EmailService`.
- Enforced mobile verification before registration email OTP.
- Enforced resend delay and verification attempt limits.
- Stored email OTP values as password hashes.
- Marked `users.email_verified_at` after successful verification.
- Marked `users.is_verified = 1` after both mobile and email are verified.
- Automatically sends the first email OTP after successful mobile verification.
- Updated the Ionic OTP verification page to support mobile and email modes.

## SMTP Configuration

Set SMTP values in `api/.env`:

```env
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=your-smtp-username
SMTP_PASSWORD=your-smtp-password
SMTP_FROM_EMAIL=no-reply@stabimatic.local
SMTP_FROM_NAME=STABIMATIC
```

If `SMTP_HOST` is blank, the API stays in development mode: it logs the email OTP event and reports success without sending a real email.

## API

### Send Email OTP

`POST /api/v1/auth/send-email-otp`

```json
{
  "user_id": 1,
  "email": "dealer@example.com",
  "purpose": "registration"
}
```

### Verify Email OTP

`POST /api/v1/auth/verify-email-otp`

```json
{
  "user_id": 1,
  "email": "dealer@example.com",
  "purpose": "registration",
  "otp": "123456"
}
```

Success:

```json
{
  "success": true,
  "message": "Email verified successfully. Account is ready for login.",
  "data": {
    "email_verified": true,
    "account_verified": true,
    "next_step": "login"
  }
}
```

## Deferred To Phase 6

- Login endpoint.
- JWT session issue on login.
- AuthGuard navigation to Dealer or Installer dashboard.
