# Phase 11 - Serial Number Validation

## Completed Scope

- Implemented Installer-only serial validation endpoint:
  - `GET /api/v1/installations/check-serial/{serial}`
- Added active Dealer sale lookup by `sales.device_serial`.
- Returned matched sale details for Installer review:
  - invoice number
  - sale date
  - customer name
  - customer mobile
  - device model
  - dealer name
  - dealer mobile
- Returned explicit unmatched status when no active Dealer sale exists.
- Added configurable setting:
  - `require_sale_before_installation`
- Seeded `require_sale_before_installation` to `true`.
- Enforced the setting during installation create and update.
- Kept soft-deleted Dealer sales out of matching.
- Added Ionic serial check action on the Installer installation form.
- Displayed matched or unmatched serial status before submission.

## API

### Check Serial

`GET /api/v1/installations/check-serial/STB00001250`

Installer only.

Successful matched response:

```json
{
  "success": true,
  "message": "Serial number matched Dealer sale",
  "data": {
    "matched": true,
    "requires_sale_before_installation": true,
    "serial_number": "STB00001250",
    "sale": {
      "id": 12,
      "invoice_number": "INV-1001",
      "sale_date": "2026-09-15",
      "customer_name": "Customer Name",
      "customer_mobile": "03000000000",
      "device_model": "STABIMATIC",
      "device_serial": "STB00001250",
      "dealer_name": "Dealer Name",
      "dealer_mobile": "03000000001"
    }
  }
}
```

Successful unmatched response:

```json
{
  "success": true,
  "message": "Serial number was not found in Dealer sales",
  "data": {
    "matched": false,
    "requires_sale_before_installation": true,
    "serial_number": "STB00001250",
    "sale": null
  }
}
```

## Business Rule

When `require_sale_before_installation` is enabled, installation create and update reject unmatched serial numbers with a validation error.

When disabled, installers may save unmatched serials, but the check endpoint still returns `matched: false` so reporting can identify unmatched installations.

## Deferred To Phase 12

- Super Admin login authentication.
- Admin session protection.
- Admin dashboard access guard.
