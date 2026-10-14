# Phase 8 - Dealer Sales Module

## Completed Scope

- Implemented Dealer-only Sales REST API:
  - `GET /api/v1/sales`
  - `GET /api/v1/sales/{id}`
  - `POST /api/v1/sales`
  - `PUT /api/v1/sales/{id}`
  - `DELETE /api/v1/sales/{id}`
- Added `SalesController`.
- Added `SalesService`.
- Expanded `SalesRepository`.
- Enforced JWT authentication and Dealer role authorization.
- Derived `dealer_id` from JWT claims only.
- Added server-side validation for:
  - invoice number
  - sale date
  - customer name
  - Pakistani customer mobile
  - device model
  - device serial
- Prevented duplicate active device serial numbers.
- Added soft delete through `deleted_at`.
- Added server-side search by invoice, customer, mobile, and serial number.
- Added server-side pagination using the common pagination response shape.
- Logged sale create, update, and delete activity.
- Added Ionic Sales list page with search, cards, pull-to-refresh, infinite scroll, and delete confirmation.
- Added Ionic Add/Edit Sale form with validation and loading states.
- Added Ionic Sale Details page with edit and delete actions.

## API

### List Sales

`GET /api/v1/sales?page=1&limit=20&search=ali`

Dealer only.

### Create Sale

`POST /api/v1/sales`

```json
{
  "invoice_number": "INV-2026-001",
  "sale_date": "2026-09-15",
  "customer_name": "Muhammad Ali",
  "customer_mobile": "03001234567",
  "device_model": "STABIMATIC X200",
  "device_serial": "STB00001250"
}
```

### Update Sale

`PUT /api/v1/sales/{id}`

### Delete Sale

`DELETE /api/v1/sales/{id}`

## Security Notes

- The API never accepts `dealer_id` from the request.
- A Dealer can only view, edit, or delete their own sales.
- Deleted records are hidden from Dealer APIs.
- Serial number uniqueness is checked before insert/update and also protected by the database active serial index.

## Deferred To Phase 9

- Installer dashboard.
- Installer-specific navigation and installation counts.
