# Phase 10 - Installation Module

## Completed Scope

- Implemented Installer-only Installation REST API:
  - `GET /api/v1/installations`
  - `GET /api/v1/installations/{id}`
  - `POST /api/v1/installations`
  - `PUT /api/v1/installations/{id}`
  - `DELETE /api/v1/installations/{id}`
- Added `InstallationsController`.
- Added `InstallationsService`.
- Expanded `InstallationsRepository`.
- Enforced JWT authentication and Installer role authorization.
- Derived `installer_id` from JWT claims only.
- Added server-side validation for:
  - customer name
  - Pakistani customer mobile
  - optional customer email
  - serial number
  - installed date/time
  - installation picture on create
- Added image upload validation:
  - allowed extensions: jpg, jpeg, png, webp
  - allowed MIME types: image/jpeg, image/png, image/webp
  - image dimension validation through `getimagesize()`
  - configurable max file size
  - unique generated filenames
- Added upload `.htaccess` guards to prevent script execution.
- Added server-side search by customer, mobile, email, and serial number.
- Added server-side pagination using the common pagination response shape.
- Added soft delete through `deleted_at`.
- Logged installation create, update, and delete activity.
- Added Ionic installation list page with search, cards, pull-to-refresh, infinite scroll, and delete confirmation.
- Added Ionic Add/Edit Installation form.
- Added Capacitor Camera and gallery image selection with preview before submission.
- Added Ionic Installation Details page with image preview, edit, and delete actions.

## API

### List Installations

`GET /api/v1/installations?page=1&limit=20&search=STB0001`

Installer only.

### Create Installation

`POST /api/v1/installations`

Content type: `multipart/form-data`

Fields:

- `customer_name`
- `customer_mobile`
- `customer_email`
- `serial_number`
- `installed_at`
- `installation_picture`

### Update Installation

`PUT /api/v1/installations/{id}`

The current mobile edit flow updates text fields only. Image replacement support exists in the backend when a valid `installation_picture` file is received by the PHP runtime.

### Delete Installation

`DELETE /api/v1/installations/{id}`

## Native Notes

- Capacitor Camera is configured in `capacitor.config.ts`.
- Platform-specific Android/iOS permission files are generated after `npx cap add android` or `npx cap add ios`.
- Android/iOS permission polishing remains in Phases 31 and 32.

## Deferred To Phase 11

- Serial number validation against Dealer sales.
- Business setting behavior for allowing or blocking unmatched serials.
