# Phase 22 - Mobile Profile

## Completed Scope

- Added API `ProfileService`.
- Added API `ProfileController`.
- Added API routes for:
  - `GET /api/v1/profile`
  - `PUT /api/v1/profile`
  - `POST /api/v1/profile/change-password`
- Protected profile endpoints with JWT authentication.
- Added current Dealer/Installer profile response.
- Added profile updates for:
  - full name
  - CNIC
  - bank name
  - bank account title
  - bank account number
  - IBAN
- Kept mobile and email read-only because they require OTP verification flows.
- Added duplicate CNIC validation.
- Added password change with current password verification.
- Stored changed passwords with `password_hash()`.
- Logged profile and password activity to `activity_logs`.
- Added Ionic `ProfileService`.
- Replaced the mobile profile placeholder with a live profile screen.
- Added mobile profile form, password form, loading state, inline validation, toast feedback, and sign out action.
- Refreshed the cached mobile user after profile updates.

## API Routes

### Current Profile

`GET /api/v1/profile`

Requires a valid Dealer or Installer JWT.

### Update Profile

`PUT /api/v1/profile`

Fields:

- `full_name`
- `cnic`
- `bank_name`
- `bank_account_title`
- `bank_account_number`
- `iban`

### Change Password

`POST /api/v1/profile/change-password`

Fields:

- `current_password`
- `new_password`
- `confirm_password`

## Deferred To Phase 23

- Forgot password API completion.
- Reset OTP verification.
- Reset password flow.
