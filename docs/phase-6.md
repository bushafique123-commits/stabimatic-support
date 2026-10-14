# Phase 6 - Login & Authentication

## Completed Scope

- Implemented `POST /api/v1/auth/login`.
- Implemented `POST /api/v1/auth/logout`.
- Added `AuthenticationService` for login and logout business logic.
- Validates mobile/email and password input.
- Normalizes mobile/email identifiers before lookup.
- Uses `password_verify()` for password checks.
- Blocks login for:
  - invalid credentials
  - inactive accounts
  - unverified mobile/email accounts
- Adds login brute-force protection through `rate_limits`.
- Issues JWT bearer tokens through `TokenService`.
- Stores JWT token IDs in `auth_tokens`.
- Revokes the current token on logout.
- Logs Dealer/Installer login and logout activity.
- Updated Ionic login screen with loading state, inline validation, API error mapping, forgot password link, register link, and role-based dashboard routing.
- Updated AuthGuard and RoleGuard to wait for restored local session state.

## API

### Login

`POST /api/v1/auth/login`

```json
{
  "identifier": "03001234567",
  "password": "StrongPass1"
}
```

Success:

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "JWT_TOKEN",
    "user": {
      "id": 15,
      "name": "Test User",
      "role": "dealer"
    }
  }
}
```

### Logout

`POST /api/v1/auth/logout`

Header:

```text
Authorization: Bearer JWT_TOKEN
```

Success:

```json
{
  "success": true,
  "message": "Logout successful",
  "data": []
}
```

## Deferred To Phase 7

- Dealer dashboard API data.
- Dealer dashboard UI completion beyond navigation shell.
