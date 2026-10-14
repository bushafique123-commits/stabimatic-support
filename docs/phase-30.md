# Phase 30 - Mobile Earnings Wallet

## Completed Scope

- Added `EarningsRepository`.
- Added `EarningsService`.
- Added `EarningsController`.
- Added API routes for:
  - `GET /api/v1/earnings/summary`
  - `GET /api/v1/earnings`
  - `GET /api/v1/earnings/payouts`
- Protected all earnings routes with JWT authentication.
- Limited earnings and payouts to the authenticated Dealer or Installer.
- Added summary totals for pending, approved, paid, cancelled, lifetime, and payouts.
- Added status filtering for earnings and payouts.
- Added mobile earning and payout models.
- Added mobile earnings service.
- Added Ionic earnings wallet module.
- Added wallet summary cards and payout summary cards.
- Added earnings and payout history views.
- Added refresh, infinite scroll, loading, empty, and error states.
- Added Profile navigation for the wallet.

## API Routes

### Summary

`GET /api/v1/earnings/summary`

Returns current commission totals for the signed-in user.

### Earnings

`GET /api/v1/earnings`

Query:

- `page`
- `limit`
- `status`

### Payouts

`GET /api/v1/earnings/payouts`

Query:

- `page`
- `limit`
- `status`

## Deferred To Phase 31

- Android Capacitor build preparation.
- Native mobile permission polishing.
- Release build documentation.
