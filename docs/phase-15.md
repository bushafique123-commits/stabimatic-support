# Phase 15 - Installer Management

## Completed Scope

- Added `AdminInstallerRepository`.
- Added `AdminInstallerService`.
- Added `InstallersController`.
- Added Installer list view.
- Added Installer detail view.
- Added admin routes for:
  - `GET /admin/installers`
  - `GET /admin/installers/{id}`
  - `POST /admin/installers/{id}/activate`
  - `POST /admin/installers/{id}/deactivate`
  - `POST /admin/installers/{id}/verify`
  - `POST /admin/installers/{id}/unverify`
- Protected Installer management routes with Super Admin session authentication.
- Protected all Installer mutation actions with CSRF verification.
- Added Installer search by:
  - full name
  - mobile
  - email
  - CNIC
- Added filters for active/inactive status.
- Added filters for verified/pending accounts.
- Added Installer list pagination.
- Added Installer detail account, contact, verification, and bank profile sections.
- Added Installer installation summary.
- Added matched and unmatched installation counts.
- Added recent Installer installations table.
- Added Super Admin activity logging for Installer account changes.

## Admin Routes

### Installer List

`GET /admin/installers`

Query parameters:

- `search`
- `status`: `active` or `inactive`
- `verification`: `verified` or `pending`
- `page`

### Installer Detail

`GET /admin/installers/{id}`

Shows account details, bank details, installation totals, serial match totals, and recent installations.

### Installer Controls

All controls require `_csrf_token`.

- `POST /admin/installers/{id}/activate`
- `POST /admin/installers/{id}/deactivate`
- `POST /admin/installers/{id}/verify`
- `POST /admin/installers/{id}/unverify`

## Deferred To Phase 16

- Super Admin sales listing.
- Sales search and filters.
- Sales detail and read-only audit view.
