# Phase 19 - Settings

## Completed Scope

- Added `AdminSettingRepository`.
- Added `AdminSettingService`.
- Added `SettingsController`.
- Added Super Admin Settings page.
- Added admin routes for:
  - `GET /admin/settings`
  - `POST /admin/settings`
- Protected Settings routes with Super Admin session authentication.
- Protected settings save with CSRF verification.
- Added editable company settings:
  - company name
  - company logo path
  - support phone
  - support email
- Added editable OTP settings:
  - OTP expiry minutes
  - OTP resend seconds
  - maximum OTP attempts
- Added editable upload setting:
  - maximum image size in MB
- Added editable serial validation setting:
  - require Dealer sale before installation
- Validated settings before database persistence.
- Stored image size as bytes in the shared `settings` table.
- Logged settings updates to `activity_logs`.
- Aligned public API fallback for `require_sale_before_installation` with the strict default policy.

## Admin Routes

### Settings Form

`GET /admin/settings`

Shows the editable system settings form.

### Save Settings

`POST /admin/settings`

Fields:

- `_csrf_token`
- `company_name`
- `company_logo`
- `support_phone`
- `support_email`
- `otp_expiry_minutes`
- `otp_resend_seconds`
- `maximum_otp_attempts`
- `maximum_image_size_mb`
- `require_sale_before_installation`

## Validation

- Company name is required and must be 150 characters or fewer.
- Support email must be valid when present.
- OTP expiry must be between 1 and 60 minutes.
- OTP resend delay must be between 15 and 600 seconds.
- Maximum OTP attempts must be between 1 and 10.
- Maximum image size must be between 1 and 20 MB.

## Deferred To Phase 20

- Activity log listing.
- Activity log search and filters.
- Activity log detail context.
