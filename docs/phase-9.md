# Phase 9 - Installer Dashboard

## Completed Scope

- Implemented `GET /api/v1/installer/dashboard`.
- Added `InstallerDashboardController`.
- Added `InstallerDashboardService`.
- Added `InstallationsRepository` dashboard query methods.
- Enforced JWT authentication and Installer role authorization.
- Derived Installer ID from JWT claims and token registry validation.
- Verified the Installer account is still active before returning dashboard data.
- Returned dashboard totals:
  - Total Installations
  - Today's Installations
  - This Month
- Returned up to five recent Installer installations.
- Updated Ionic Installer dashboard to load API data.
- Added loading, pull-to-refresh, empty, and error states.
- Added bottom navigation for Home, Installations, Add Installation, and Profile.

## API

### Installer Dashboard

`GET /api/v1/installer/dashboard`

Header:

```text
Authorization: Bearer JWT_TOKEN
```

Installer only.

Success:

```json
{
  "success": true,
  "message": "Installer dashboard loaded",
  "data": {
    "installer": {
      "id": 22,
      "name": "Test Installer"
    },
    "stats": {
      "total_installations": 12,
      "installations_today": 1,
      "installations_this_month": 4
    },
    "recent_installations": []
  }
}
```

## Deferred To Phase 10

- Installation list page.
- Add Installation page.
- Camera/gallery image capture.
- Multipart image upload.
- Installation edit/delete/detail workflows.
