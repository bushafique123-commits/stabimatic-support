# Phase 25 - Notifications

## Completed Scope

- Added `device_tokens` table for mobile push token registration.
- Added `notification_preferences` table for per-user notification categories.
- Added `NotificationRepository`.
- Added `NotificationService`.
- Added `NotificationController`.
- Added API routes for:
  - `POST /api/v1/notifications/device-tokens`
  - `DELETE /api/v1/notifications/device-tokens`
  - `GET /api/v1/notifications/preferences`
  - `PUT /api/v1/notifications/preferences`
- Protected all notification routes with JWT authentication.
- Validated notification platform values as `ios`, `android`, or `web`.
- Stored device name, app version, active state, and last-seen timestamps.
- Supported idempotent device token refresh through an upsert.
- Supported device token deactivation for logout/uninstall cleanup.
- Returned default notification preferences before a user customizes them.
- Logged device registration, device removal, and preference updates to `activity_logs`.
- Added mobile notification models and service methods.
- Added Ionic profile notification toggles for sales, installations, account, and offers.

## API Routes

### Register Device Token

`POST /api/v1/notifications/device-tokens`

Fields:

- `platform`
- `device_token`
- `device_name`
- `app_version`

### Remove Device Token

`DELETE /api/v1/notifications/device-tokens`

Fields:

- `device_token`

### Load Preferences

`GET /api/v1/notifications/preferences`

### Save Preferences

`PUT /api/v1/notifications/preferences`

Fields:

- `sales_notifications`
- `installation_notifications`
- `account_notifications`
- `marketing_notifications`

## Deferred To Phase 26

- Super Admin notification broadcasting.
- Queued notification delivery tracking.
- Push provider integration for FCM/APNs.
