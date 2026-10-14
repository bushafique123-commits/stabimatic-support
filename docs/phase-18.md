# Phase 18 - Reports

## Completed Scope

- Added `AdminReportRepository`.
- Added `AdminReportService`.
- Added `ReportsController`.
- Added Super Admin Reports page.
- Added admin route:
  - `GET /admin/reports`
- Protected Reports route with Super Admin session authentication.
- Added date-bounded report filters:
  - `date_from`
  - `date_to`
- Defaulted reports to the current month when no date range is provided.
- Added summary metrics for:
  - sales
  - installations
  - matched installations
  - unmatched installations
  - Dealer activity
  - Installer activity
  - Admin activity
- Added serial match-rate meter.
- Added daily sales table.
- Added daily installations table.
- Added top Dealers table.
- Added top Installers table.
- Added unmatched installations table.
- Added activity breakdown by role, module, and action.

## Admin Route

### Reports

`GET /admin/reports`

Query parameters:

- `date_from`
- `date_to`

## Data Sources

- `sales`
- `installations`
- `users`
- `activity_logs`

## Deferred To Phase 19

- Editable system settings.
- Company profile settings.
- OTP and upload settings.
- Serial validation policy setting.
