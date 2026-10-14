# Phase 29 - Commissions

## Completed Scope

- Added `commission_rules` table.
- Added `commission_earnings` table.
- Added `commission_payouts` table.
- Added `AdminCommissionRepository`.
- Added `AdminCommissionService`.
- Added `CommissionsController`.
- Added Super Admin routes for:
  - `GET /admin/commissions`
  - `POST /admin/commissions/rules`
  - `POST /admin/commissions/generate`
  - `POST /admin/commissions/approve-pending`
  - `POST /admin/commissions/create-payouts`
  - `POST /admin/commissions/payouts/{id}/status`
- Added the Commissions link to the Super Admin sidebar.
- Added editable Dealer sale and Installer installation commission rates.
- Added idempotent earning generation from non-deleted sales and installations.
- Added commission earning status workflow: `pending`, `approved`, `paid`, `cancelled`.
- Added payout records grouped by user from approved earnings.
- Added paid/cancelled payout updates with reference and notes.
- Added commission dashboard stats and filters.
- Logged rule updates, earning generation, approvals, payout creation, and payout updates.

## Admin Routes

### Commission Dashboard

`GET /admin/commissions`

Shows rates, operations, earnings, filters, and recent payouts.

### Save Rules

`POST /admin/commissions/rules`

Fields:

- `dealer_sale_amount`
- `installer_installation_amount`

### Generate Earnings

`POST /admin/commissions/generate`

Creates or refreshes missing pending earnings from sales and installations.

### Approve Pending

`POST /admin/commissions/approve-pending`

Approves all pending earnings.

### Create Payouts

`POST /admin/commissions/create-payouts`

Groups approved unpaid earnings into pending payout records.

### Update Payout

`POST /admin/commissions/payouts/{id}/status`

Fields:

- `status`
- `payment_reference`
- `notes`

## Deferred To Phase 30

- Mobile earnings wallet.
- Dealer/Installer payout history.
- Mobile commission summaries.
