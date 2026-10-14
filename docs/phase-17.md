# Phase 17 - Installation Management

## Completed Scope

- Added `AdminInstallationRepository`.
- Added `AdminInstallationService`.
- Added admin `InstallationsController`.
- Added Installation list view.
- Added Installation detail view.
- Added admin routes for:
  - `GET /admin/installations`
  - `GET /admin/installations/{id}`
- Protected Installation management routes with Super Admin session authentication.
- Added read-only installation audit workflow.
- Added Installation search by:
  - customer name
  - customer mobile
  - customer email
  - serial number
  - Installer name
  - Installer mobile
  - matched invoice number
- Added filters for:
  - Installer ID
  - installed date from
  - installed date to
  - matched or unmatched Dealer sale status
- Added Installation list pagination.
- Added Installer context on list and detail pages.
- Added matched Dealer sale context on list and detail pages.
- Added installation image links and detail image preview.
- Added related installation activity feed.

## Admin Routes

### Installation List

`GET /admin/installations`

Query parameters:

- `search`
- `installer_id`
- `date_from`
- `date_to`
- `match`: `matched` or `unmatched`
- `page`

### Installation Detail

`GET /admin/installations/{id}`

Shows installation, customer, Installer, matched Dealer sale, image, and activity context.

## Management Policy

Installation management is read-only in the Super Admin panel. Installer-created installation records remain owned by the Installer mobile workflow, while the admin panel provides operational review, serial-match inspection, and image access.

## Deferred To Phase 18

- Sales and installation reports.
- Serial matching reports.
- User activity reports.
