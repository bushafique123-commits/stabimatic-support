# Phase 16 - Sales Management

## Completed Scope

- Added `AdminSaleRepository`.
- Added `AdminSaleService`.
- Added admin `SalesController`.
- Added Sales list view.
- Added Sales detail view.
- Added admin routes for:
  - `GET /admin/sales`
  - `GET /admin/sales/{id}`
- Protected Sales management routes with Super Admin session authentication.
- Added read-only sales audit workflow.
- Added Sales search by:
  - invoice number
  - customer name
  - customer mobile
  - device serial
  - Dealer name
  - Dealer mobile
- Added filters for:
  - Dealer ID
  - sale date from
  - sale date to
  - matched or unmatched installation status
- Added Sales list pagination.
- Added Dealer context on sale list and sale detail.
- Added matched installation count and latest installation date on the list.
- Added sale detail view with matched installation records.
- Added links from matched installations to Installer profiles.
- Added related sale activity feed.

## Admin Routes

### Sales List

`GET /admin/sales`

Query parameters:

- `search`
- `dealer_id`
- `date_from`
- `date_to`
- `match`: `matched` or `unmatched`
- `page`

### Sale Detail

`GET /admin/sales/{id}`

Shows sale, customer, Dealer, matched installation, and activity context.

## Management Policy

Sales management is read-only in the Super Admin panel. Dealer-created sales remain owned by the Dealer mobile workflow, while the admin panel provides operational review and audit visibility.

## Deferred To Phase 17

- Super Admin installation listing.
- Installation search and filters.
- Installation detail and image inspection.
