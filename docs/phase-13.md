# Phase 13 - Super Admin Dashboard

## Completed Scope

- Added `AdminDashboardRepository`.
- Added `AdminDashboardService`.
- Replaced static dashboard zeros with live database metrics.
- Added Dealer totals:
  - total dealers
  - active dealers
  - pending dealers
- Added Installer totals:
  - total installers
  - active installers
  - pending installers
- Added sales metrics:
  - active sales
  - sales today
- Added installation metrics:
  - active installations
  - installations today
- Added serial matching metrics:
  - matched installations
  - unmatched installations
  - match rate
- Added operational status panel:
  - database status
  - serial validation policy
  - unmatched installation count
  - OTP expiry
  - OTP resend delay
  - image upload limit
- Added recent activity feed from `activity_logs`.
- Added dashboard unavailable fallback data for database errors.
- Updated dashboard layout and responsive CSS.

## Admin Route

`GET /admin/dashboard`

Requires an active Super Admin session.

## Data Sources

- `users`
- `sales`
- `installations`
- `activity_logs`
- `settings`

## Deferred To Phase 14

- Dealer management listing.
- Dealer search and filters.
- Dealer account activation and verification controls.
