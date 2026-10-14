# STABIMATIC API Endpoint Catalogue

Base path: `/api/v1`

All responses use:

```json
{
  "success": true,
  "message": "Message",
  "data": {}
}
```

Errors use:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {}
}
```

## Foundation

| Method | URL | Auth | Role | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/health` | No | Public | API and database readiness |
| GET | `/settings/public` | No | Public | Non-sensitive runtime settings |

## Authentication

| Method | URL | Auth | Role | Purpose |
| --- | --- | --- | --- | --- |
| POST | `/auth/register` | No | Dealer, Installer | Create pending account |
| POST | `/auth/send-mobile-otp` | No | Dealer, Installer | Send or resend mobile OTP |
| POST | `/auth/verify-mobile-otp` | No | Dealer, Installer | Verify mobile OTP |
| POST | `/auth/send-email-otp` | No | Dealer, Installer | Send or resend email OTP |
| POST | `/auth/verify-email-otp` | No | Dealer, Installer | Verify email OTP |
| POST | `/auth/login` | No | Dealer, Installer | Issue JWT |
| POST | `/auth/logout` | Yes | Dealer, Installer | Revoke or log out session |
| POST | `/auth/forgot-password` | No | Dealer, Installer | Send reset OTP |
| POST | `/auth/verify-reset-otp` | No | Dealer, Installer | Verify reset OTP |
| POST | `/auth/reset-password` | No | Dealer, Installer | Set new password |

## Profile

| Method | URL | Auth | Role | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/profile` | Yes | Dealer, Installer | Current user profile |
| PUT | `/profile` | Yes | Dealer, Installer | Update permitted profile fields |
| POST | `/profile/change-password` | Yes | Dealer, Installer | Change password |
| POST | `/profile/change-mobile/send-otp` | Yes | Dealer, Installer | Send OTP to new mobile number |
| POST | `/profile/change-mobile/verify` | Yes | Dealer, Installer | Verify OTP and change mobile number |
| POST | `/profile/change-email/send-otp` | Yes | Dealer, Installer | Send OTP to new email address |
| POST | `/profile/change-email/verify` | Yes | Dealer, Installer | Verify OTP and change email address |

## Notifications

| Method | URL | Auth | Role | Purpose |
| --- | --- | --- | --- | --- |
| POST | `/notifications/device-tokens` | Yes | Dealer, Installer | Register or refresh a push notification device token |
| DELETE | `/notifications/device-tokens` | Yes | Dealer, Installer | Deactivate a push notification device token |
| GET | `/notifications/preferences` | Yes | Dealer, Installer | Load notification category preferences |
| PUT | `/notifications/preferences` | Yes | Dealer, Installer | Save notification category preferences |
| GET | `/notifications` | Yes | Dealer, Installer | Paginated mobile notification inbox |
| GET | `/notifications/unread-count` | Yes | Dealer, Installer | Current unread notification count |
| GET | `/notifications/{id}` | Yes | Dealer, Installer | Notification detail and automatic read marking |
| POST | `/notifications/{id}/read` | Yes | Dealer, Installer | Mark one notification as read |
| POST | `/notifications/read-all` | Yes | Dealer, Installer | Mark all notifications as read |

## Earnings

| Method | URL | Auth | Role | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/earnings/summary` | Yes | Dealer, Installer | Current commission totals and payout totals |
| GET | `/earnings` | Yes | Dealer, Installer | Paginated commission earning history |
| GET | `/earnings/payouts` | Yes | Dealer, Installer | Paginated commission payout history |

## Dealer Dashboard

| Method | URL | Auth | Role | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/dealer/dashboard` | Yes | Dealer | Dealer totals and recent sales |

## Installer Dashboard

| Method | URL | Auth | Role | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/installer/dashboard` | Yes | Installer | Installer totals and recent installations |

## Dealer Sales

| Method | URL | Auth | Role | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/sales` | Yes | Dealer | Paginated sale list |
| GET | `/sales/{id}` | Yes | Dealer | Sale detail |
| POST | `/sales` | Yes | Dealer | Create sale |
| PUT | `/sales/{id}` | Yes | Dealer | Update sale |
| DELETE | `/sales/{id}` | Yes | Dealer | Soft-delete sale |

## Installer Installations

| Method | URL | Auth | Role | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/installations` | Yes | Installer | Paginated installation list |
| GET | `/installations/check-serial/{serial}` | Yes | Installer | Check active Dealer sale serial |
| GET | `/installations/{id}` | Yes | Installer | Installation detail |
| POST | `/installations` | Yes | Installer | Create installation with image upload |
| PUT | `/installations/{id}` | Yes | Installer | Update installation |
| DELETE | `/installations/{id}` | Yes | Installer | Soft-delete installation |

## Admin Web Routes

Admin uses `/admin/` session routes rather than mobile JWT routes.

| Method | URL | Purpose |
| --- | --- | --- |
| GET | `/admin/login` | Login screen |
| POST | `/admin/login` | Authenticate admin |
| POST | `/admin/logout` | Destroy admin session |
| GET | `/admin/dashboard` | Admin dashboard |
| GET | `/admin/dealers` | Dealer listing and filters |
| GET | `/admin/dealers/{id}` | Dealer detail |
| POST | `/admin/dealers/{id}/activate` | Activate Dealer |
| POST | `/admin/dealers/{id}/deactivate` | Deactivate Dealer |
| POST | `/admin/dealers/{id}/verify` | Mark Dealer verified |
| POST | `/admin/dealers/{id}/unverify` | Mark Dealer pending |
| GET | `/admin/installers` | Installer listing and filters |
| GET | `/admin/installers/{id}` | Installer detail |
| POST | `/admin/installers/{id}/activate` | Activate Installer |
| POST | `/admin/installers/{id}/deactivate` | Deactivate Installer |
| POST | `/admin/installers/{id}/verify` | Mark Installer verified |
| POST | `/admin/installers/{id}/unverify` | Mark Installer pending |
| GET | `/admin/sales` | Sales listing and filters |
| GET | `/admin/sales/{id}` | Sale detail and matched installations |
| GET | `/admin/installations` | Installation listing and filters |
| GET | `/admin/installations/{id}` | Installation detail and matched sale |
| GET | `/admin/reports` | Sales, installation, serial matching, and activity reports |
| GET | `/admin/notifications` | Notification broadcast form and queued batch list |
| POST | `/admin/notifications` | Queue a notification broadcast |
| GET | `/admin/notifications/{batch}` | Notification batch detail and recipient delivery review |
| POST | `/admin/notifications/{batch}/status` | Update queued notification batch status |
| GET | `/admin/commissions` | Commission rules, earnings, and payout tracking |
| POST | `/admin/commissions/rules` | Save Dealer and Installer commission rates |
| POST | `/admin/commissions/generate` | Generate missing commission earnings |
| POST | `/admin/commissions/approve-pending` | Approve pending commission earnings |
| POST | `/admin/commissions/create-payouts` | Create payout records from approved earnings |
| POST | `/admin/commissions/payouts/{id}/status` | Mark a payout paid or cancelled |
| GET | `/admin/settings` | Editable system settings |
| POST | `/admin/settings` | Save system settings |
| GET | `/admin/activity-logs` | Searchable activity log listing and filters |
| GET | `/admin/activity-logs/{id}` | Activity log detail context |
| GET | `/admin/profile` | Super Admin profile and password forms |
| POST | `/admin/profile` | Update Super Admin profile |
| POST | `/admin/profile/password` | Change Super Admin password |

## Console Commands

| Command | Purpose |
| --- | --- |
| `php api/console/process-notifications.php --limit=50` | Process queued notification rows through push, SMS, or email providers |
