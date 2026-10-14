# Phase 27 - Mobile Notification Inbox

## Completed Scope

- Added `read_at` tracking to notification rows.
- Added API support for the mobile inbox.
- Added API routes for:
  - `GET /api/v1/notifications`
  - `GET /api/v1/notifications/unread-count`
  - `GET /api/v1/notifications/{id}`
  - `POST /api/v1/notifications/{id}/read`
  - `POST /api/v1/notifications/read-all`
- Protected all inbox routes with JWT authentication.
- Limited inbox data to the current Dealer or Installer.
- Added read/unread filtering.
- Added notification category filtering.
- Automatically marked a notification read when opened.
- Added single notification mark-read support.
- Added bulk mark-all-read support.
- Expanded the mobile notification model and service.
- Added Ionic notification inbox list and detail pages.
- Added unread count display, filters, refresh, infinite scroll, and empty/error states.
- Added notification navigation from Dealer dashboard, Installer dashboard, and Profile.

## API Routes

### Inbox

`GET /api/v1/notifications`

Query:

- `page`
- `limit`
- `status`
- `notification_type`

### Unread Count

`GET /api/v1/notifications/unread-count`

### Notification Detail

`GET /api/v1/notifications/{id}`

Opening a notification through this endpoint marks it read.

### Mark Read

`POST /api/v1/notifications/{id}/read`

### Mark All Read

`POST /api/v1/notifications/read-all`

## Deferred To Phase 28

- Notification delivery worker.
- Push provider dispatch for queued push notifications.
- SMS and email broadcast dispatch.
