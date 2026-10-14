# Phase 14 - Dealer Management

## Completed Scope

- Added `AdminDealerRepository`.
- Added `AdminDealerService`.
- Added `DealersController`.
- Added Dealer list view.
- Added Dealer detail view.
- Added admin routes for:
  - `GET /admin/dealers`
  - `GET /admin/dealers/{id}`
  - `POST /admin/dealers/{id}/activate`
  - `POST /admin/dealers/{id}/deactivate`
  - `POST /admin/dealers/{id}/verify`
  - `POST /admin/dealers/{id}/unverify`
- Protected Dealer management routes with Super Admin session authentication.
- Protected all Dealer mutation actions with CSRF verification.
- Added Dealer search by:
  - full name
  - mobile
  - email
  - CNIC
- Added filters for active/inactive status.
- Added filters for verified/pending accounts.
- Added Dealer list pagination.
- Added Dealer detail account, contact, verification, and bank profile sections.
- Added Dealer sales summary.
- Added recent Dealer sales table.
- Added Super Admin activity logging for account changes.

## Admin Routes

### Dealer List

`GET /admin/dealers`

Query parameters:

- `search`
- `status`: `active` or `inactive`
- `verification`: `verified` or `pending`
- `page`

### Dealer Detail

`GET /admin/dealers/{id}`

Shows account details, bank details, sales totals, and recent sales.

### Dealer Controls

All controls require `_csrf_token`.

- `POST /admin/dealers/{id}/activate`
- `POST /admin/dealers/{id}/deactivate`
- `POST /admin/dealers/{id}/verify`
- `POST /admin/dealers/{id}/unverify`

## Deferred To Phase 15

- Installer management listing.
- Installer search and filters.
- Installer activation and verification controls.
