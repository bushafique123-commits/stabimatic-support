# STABIMATIC Phase 1 Blueprint

## 1. Complete Recommended Folder Structure

```text
stabimatic/
  mobile-app/
    src/
      app/
        core/
          guards/
          interceptors/
          models/
          services/
        shared/
          components/
            empty-state/
            input-field/
            loading-state/
            page-header/
            primary-button/
            stat-card/
        pages/
          auth/
          dealer/
          installer/
          profile/
      environments/
      theme/
    android/
    ios/
  api/
    config/
    controllers/
    core/
    helpers/
    logs/
    middleware/
    models/
    repositories/
    routes/
    services/
    uploads/
      installations/
  admin/
    assets/
      css/
      js/
    config/
    controllers/
    core/
    middleware/
    public/
    views/
      auth/
      dashboard/
      layouts/
      partials/
  database/
  docs/
```

## 2. Database ERD Description

`users` stores Dealer and Installer accounts. A user has one role, unique mobile/email, optional unique CNIC, bank profile fields, verification timestamps, account status, and secure password hash.

`otp_verifications` stores expiring mobile/email OTP records for registration, forgot password, and profile changes. OTP rows belong to a user when available, but can also be tied to a mobile/email before full account verification.

`sales` stores Dealer-created sale records. Each sale belongs to one Dealer user and uses soft deletion.

`installations` stores Installer-created installation records. Each installation belongs to one Installer user and uses soft deletion. Its serial number can be matched against `sales.device_serial`.

`admin_users` stores Super Admin accounts for the web application. Admin authentication is separate from mobile JWT authentication.

`activity_logs` records important user and admin actions, including login, registration, sale changes, installation changes, account status changes, and password changes.

`settings` stores editable system settings, including company branding, OTP rules, upload limits, and sale-before-installation policy.

## 3. Full MySQL Database Schema

The full schema is available in `database/schema.sql`. It includes tables, foreign keys, unique constraints, indexes, soft-delete columns, timestamps, seed settings, and a default admin creation script path.

## 4. API Endpoint List

All API routes are versioned under `/api/v1`. The endpoint catalogue is maintained in `docs/api-endpoints.md`.

## 5. Ionic Page, Component, And Service Structure

Pages:

- `auth/login`
- `auth/register`
- `auth/otp-verification`
- `auth/forgot-password`
- `dealer/dashboard`
- `dealer/sales`
- `dealer/sale-form`
- `dealer/sale-detail`
- `installer/dashboard`
- `installer/installations`
- `installer/installation-form`
- `installer/installation-detail`
- `profile/profile`
- `profile/change-password`

Core services:

- `ApiService`
- `AuthService`
- `NetworkService`
- `ToastService`
- `LoadingService`

Core guards/interceptors:

- `AuthGuard`
- `RoleGuard`
- `AuthInterceptor`
- `ErrorInterceptor`

Shared components:

- `PrimaryButtonComponent`
- `InputFieldComponent`
- `PageHeaderComponent`
- `EmptyStateComponent`
- `LoadingStateComponent`
- `StatCardComponent`

## 6. Super Admin Module Structure

Modules:

- Auth
- Dashboard
- Dealers
- Installers
- Sales
- Installations
- Reports
- Settings
- Activity Logs

Admin uses session authentication, CSRF tokens, secure cookies, audit logging, Bootstrap 5 compatible markup, AJAX-ready endpoints, and a responsive sidebar layout.

## 7. Authentication Workflow

1. User chooses Dealer or Installer.
2. User submits registration details.
3. API validates role, identity fields, bank fields, password, and Terms acceptance.
4. API creates a pending account and sends mobile OTP.
5. User verifies mobile OTP.
6. API sends email OTP.
7. User verifies email OTP.
8. API marks `mobile_verified_at`, `email_verified_at`, and `is_verified`.
9. User logs in with mobile/email and password.
10. API returns a JWT containing authenticated user ID, role, expiry, and token ID.
11. Mobile app stores token securely where possible and attaches `Authorization: Bearer`.
12. Expired or invalid JWT responses trigger logout and redirect to login.

## 8. Dealer Workflow

1. Dealer logs in after verification.
2. Dealer dashboard loads sale totals and recent sales.
3. Dealer creates sale records using invoice, date, customer, device model, and device serial.
4. API derives `dealer_id` from JWT, never from request input.
5. Dealer can list, search, view, edit, and soft-delete only their own sales.
6. Important sale actions are written to `activity_logs`.

## 9. Installer Workflow

1. Installer logs in after verification.
2. Installer dashboard loads installation totals and recent installations.
3. Installer enters serial number and can check whether it exists in Dealer sales.
4. Installer captures or chooses an image through Capacitor Camera.
5. App previews the image before upload.
6. API validates MIME type, extension, size, and authenticated Installer role.
7. API derives `installer_id` from JWT, stores the image path, and logs activity.

## 10. Sale-To-Installation Serial Matching Workflow

1. Installer enters a serial number.
2. App calls `GET /api/v1/installations/check-serial/{serial}`.
3. API searches active Dealer sales by `device_serial`.
4. If matched, API returns non-sensitive customer/device assistance data.
5. If unmatched, API returns `valid: false`.
6. Business setting `require_sale_before_installation` decides whether unmatched serials warn or block in later phases.
7. Admin reports compare `installations.serial_number` to `sales.device_serial` to show Matched or Unmatched status.
