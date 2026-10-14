# Phase 26 - Admin Notification Center

## Completed Scope

- Added `notifications` table with batch IDs and per-recipient delivery rows.
- Added `AdminNotificationRepository`.
- Added `AdminNotificationService`.
- Added `NotificationsController`.
- Added Super Admin routes for:
  - `GET /admin/notifications`
  - `POST /admin/notifications`
  - `GET /admin/notifications/{batch}`
  - `POST /admin/notifications/{batch}/status`
- Added the Notifications link to the Super Admin sidebar.
- Added broadcast creation with audience, channel, category, title, message, and target URL.
- Expanded each broadcast into eligible active and verified Dealer/Installer recipients.
- Honored user notification preferences for sales, installation, account, and offers.
- Limited push-channel broadcasts to users with active device tokens.
- Added broadcast queue list with status counts.
- Added broadcast detail page with recipient delivery review.
- Added status controls for marking queued notifications as sent, failed, or cancelled.
- Logged broadcast creation and status updates in `activity_logs`.

## Admin Routes

### Notification Center

`GET /admin/notifications`

Shows notification statistics, broadcast form, filters, and batch list.

### Queue Broadcast

`POST /admin/notifications`

Fields:

- `audience`
- `channel`
- `notification_type`
- `title`
- `message`
- `target_url`

### Batch Detail

`GET /admin/notifications/{batch}`

Shows broadcast metadata, status counts, and recipient rows.

### Update Batch Status

`POST /admin/notifications/{batch}/status`

Fields:

- `status`
- `error_message`

## Deferred To Phase 27

- Mobile notification inbox.
- Read/unread notification state.
- Mobile notification detail navigation.
