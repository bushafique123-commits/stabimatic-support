# Phase 20 - Activity Logs

## Completed Scope

- Added `AdminActivityLogRepository`.
- Added `AdminActivityLogService`.
- Added `ActivityLogsController`.
- Added Super Admin Activity Logs list page.
- Added Super Admin Activity Log detail page.
- Added admin routes for:
  - `GET /admin/activity-logs`
  - `GET /admin/activity-logs/{id}`
- Protected Activity Log routes with Super Admin session authentication.
- Added activity log search across action, module, description, actor ID, record ID, IP address, and user agent.
- Added filters for:
  - actor role
  - module
  - action
  - date range
- Added pagination for activity log browsing.
- Added actor context from `admin_users` and `users`.
- Added record links for supported modules:
  - dealers
  - installers
  - sales
  - installations
- Added request context detail for IP address and user agent.

## Admin Routes

### Activity Log List

`GET /admin/activity-logs`

Query parameters:

- `search`
- `user_type`
- `module`
- `action`
- `date_from`
- `date_to`
- `page`
- `limit`

### Activity Log Detail

`GET /admin/activity-logs/{id}`

Shows one audit event with actor, module, record, IP address, and user agent context.

## Deferred To Phase 21

- Profile view.
- Profile editing.
- Password change.
