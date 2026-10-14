# Phase 12 - Super Admin Authentication

## Completed Scope

- Implemented Super Admin login for the web application.
- Added `AdminUserRepository` for admin lookup and activity logging.
- Added `AdminAuthService` for login/logout business logic.
- Added `AdminAuthMiddleware` for guarded admin pages.
- Verified admin passwords with `password_verify()`.
- Regenerated the PHP session ID after successful login.
- Stored only non-sensitive admin identity data in the session.
- Updated logout to clear session data, expire the session cookie, and destroy the session.
- Added an application-owned PHP session save path.
- Added CSRF validation on login and logout.
- Added a dedicated auth layout for the login page.
- Added a sidebar logout form with CSRF token.
- Protected the dashboard through the admin auth middleware.

## Routes

### Login Page

`GET /admin/login`

Shows the Super Admin login form. Already-authenticated admins are redirected to `/admin/dashboard`.

### Login Submit

`POST /admin/login`

Fields:

- `_csrf_token`
- `email`
- `password`

Successful login redirects to `/admin/dashboard`.

Failed login redirects back to `/admin/login` with a flash error.

### Logout

`POST /admin/logout`

Requires `_csrf_token`. Destroys the admin session and redirects to `/admin/login`.

### Dashboard

`GET /admin/dashboard`

Requires an authenticated, active Super Admin session.

## Setup

Create or update a Super Admin account with:

```bash
php database/create_super_admin.php "Super Admin" admin@example.com "StrongPassword123!"
```

The script uses `api/.env` database settings.

Admin sessions default to `admin/storage/sessions`. Override with `SESSION_PATH` in `admin/.env` if your deployment needs a different writable path.

## Deferred To Phase 13

- Live dashboard metrics.
- Recent activity feed.
- Admin status panels.
