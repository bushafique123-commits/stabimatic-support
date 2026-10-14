# Phase 4 - Mobile OTP Verification

## Completed Scope

- Implemented `POST /api/v1/auth/send-mobile-otp`.
- Implemented `POST /api/v1/auth/verify-mobile-otp`.
- Added `MobileOtpService` for resend and verification business logic.
- Enforced configurable resend delay using `OTP_RESEND_SECONDS`.
- Enforced configurable maximum OTP attempts using `OTP_MAX_ATTEMPTS`.
- Added verification rate limiting through `rate_limits`.
- Kept OTP values hashed in `otp_verifications`.
- Prevented production OTP exposure; `development_otp` is only returned when `OTP_EXPOSE_IN_RESPONSE=true`.
- Marked `users.mobile_verified_at` after successful verification.
- Added activity logs for OTP resend and mobile verification.
- Wired the Ionic OTP page to verify and resend mobile OTPs.
- Added configurable SendPK SMS provider support for production OTP delivery.

## SendPK SMS Configuration

SendPK fixed/OTP SMS uses `https://sendpk.com/api/sms.php` with `api_key`, `sender`, `mobile`, `template_id`, and JSON `message` values matching an approved template.

See `docs/sendpk-sms.md` for the provider setup notes.

Set these in `api/.env`:

```env
SMS_PROVIDER=sendpk
SENDPK_API_URL=https://sendpk.com/api/sms.php
SENDPK_API_KEY=your-sendpk-api-key
SENDPK_SENDER=8584
SENDPK_OTP_TEMPLATE_ID=your-approved-template-id
SENDPK_OTP_MESSAGE_TEMPLATE={"otp":"{otp}"}
SENDPK_TIMEOUT_SECONDS=15
```

`SENDPK_OTP_MESSAGE_TEMPLATE` must match your approved template variables. For example, if your template has `#name#` and `#otp#`, use:

```env
SENDPK_OTP_MESSAGE_TEMPLATE={"name":"STABIMATIC","otp":"{otp}"}
```

## API

### Resend Mobile OTP

`POST /api/v1/auth/send-mobile-otp`

```json
{
  "user_id": 1,
  "mobile": "03001234567",
  "purpose": "registration"
}
```

### Verify Mobile OTP

`POST /api/v1/auth/verify-mobile-otp`

```json
{
  "user_id": 1,
  "mobile": "03001234567",
  "purpose": "registration",
  "otp": "123456"
}
```

Success:

```json
{
  "success": true,
  "message": "Mobile number verified successfully.",
  "data": {
    "mobile_verified": true,
    "next_step": "verify_email"
  }
}
```

## Deferred To Phase 5

- Email OTP sending.
- Email OTP verification.
- Final `is_verified = 1` account activation after both mobile and email verification.
