# Phase 21 - Profile

## Completed Scope

- Added `AdminProfileService`.
- Added `ProfileController`.
- Added Super Admin Profile page.
- Added admin routes for:
  - `GET /admin/profile`
  - `POST /admin/profile`
  - `POST /admin/profile/password`
- Protected Profile routes with Super Admin session authentication.
- Protected profile and password forms with CSRF verification.
- Added editable Super Admin profile fields:
  - name
  - email
- Added duplicate admin email validation.
- Updated the active session after profile changes.
- Added password change with current password verification.
- Added password strength validation:
  - at least 12 characters
  - uppercase character
  - lowercase character
  - number character
- Stored changed passwords with `password_hash()`.
- Logged profile and password updates to `activity_logs`.
- Added Profile navigation in the Super Admin shell.

## Admin Routes

### Profile

`GET /admin/profile`

Shows Super Admin account details and password form.

### Update Profile

`POST /admin/profile`

Fields:

- `_csrf_token`
- `name`
- `email`

### Change Password

`POST /admin/profile/password`

Fields:

- `_csrf_token`
- `current_password`
- `new_password`
- `confirm_password`

## Deferred To Phase 22

- Dealer mobile profile view.
- Installer mobile profile view.
- Mobile profile updates.
- Mobile password changes.
