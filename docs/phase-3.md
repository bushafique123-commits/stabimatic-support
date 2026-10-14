# Phase 3 - Registration System

## Completed Scope

- Implemented `POST /api/v1/auth/register`.
- Added `AuthController` and `RegistrationService`.
- Added `InputNormalizer` for registration input.
- Added server-side validation for:
  - Dealer/Installer role
  - Pakistani mobile format
  - Email
  - CNIC
  - Bank fields
  - IBAN
  - Strong password
  - Password confirmation
  - Terms acceptance
- Added duplicate prevention for mobile, email, and CNIC.
- Created pending Dealer/Installer users with secure `password_hash()`.
- Created hashed mobile OTP records for registration.
- Sent initial mobile OTP through `DummySmsProvider`.
- Added registration attempt throttling with `rate_limits`.
- Expanded the Ionic registration screen to collect all required fields.
- Wired the mobile registration form to the API with loading state, validation messages, and OTP screen navigation.

## API

### Register

`POST /api/v1/auth/register`

Request:

```json
{
  "role": "dealer",
  "full_name": "Test Dealer",
  "mobile": "03001234567",
  "email": "dealer@example.com",
  "cnic": "3520212345678",
  "bank_name": "Example Bank",
  "bank_account_title": "Test Dealer",
  "bank_account_number": "1234567890",
  "iban": "PK36SCBL0000001123456702",
  "password": "StrongPass1",
  "confirm_password": "StrongPass1",
  "terms_accepted": true
}
```

Success:

```json
{
  "success": true,
  "message": "Registration successful. Mobile OTP sent.",
  "data": {
    "user_id": 1,
    "role": "dealer",
    "mobile": "03001234567",
    "email": "dealer@example.com",
    "next_step": "verify_mobile",
    "otp_expires_in_minutes": 10,
    "otp_resend_seconds": 60
  }
}
```

## Deferred To Phase 4

- Mobile OTP verification endpoint.
- Resend mobile OTP endpoint.
- OTP attempt limit enforcement during verification.
- Mobile OTP UI behavior beyond navigation target.
