# STABIMATIC

STABIMATIC is a production-oriented system composed of an Ionic Angular mobile app, a PHP 7.4 REST API, a MySQL database, and a Super Admin web application.

This repository is currently scoped through **Phase 37**: project architecture, database setup, core API foundations, registration, OTP verification, login, JWT authentication, Dealer features, Installer dashboard, Installer installation management, serial number validation, Super Admin authentication, live Super Admin dashboard, Dealer management, Installer management, Sales management, Installation management, Reports, Settings, Activity Logs, Profile, Mobile Profile, Forgot Password, Change Mobile And Email, Notifications, Admin Notification Center, Mobile Notification Inbox, Notification Delivery Worker, Commissions, Mobile Earnings Wallet, Android Build Preparation, Android Development Readiness, Android QA And Release Candidate, Production Android Release, Android Physical Device QA, Android Store Assets And Handoff, and Production API Deployment Configuration.

## Structure

```text
stabimatic/
  mobile-app/   Ionic Angular Capacitor application
  api/          PHP 7.4 REST API
  admin/        PHP Super Admin web application
  database/     MySQL schema and setup scripts
  docs/         Architecture and API planning documents
```

## Requirements

- PHP 7.4 with PDO MySQL enabled
- MySQL 5.7+ or MySQL 8+
- Node.js LTS
- Ionic CLI
- Capacitor CLI
- Android Studio for Android builds
- Xcode only when iOS build preparation is resumed

## Phase 1 Artifacts

- Recommended folder structure
- Database ERD description
- Full MySQL schema draft
- Versioned API endpoint list
- Ionic page/component/service structure
- Super Admin module structure
- Authentication, Dealer, Installer, and serial matching workflows
- PHP API core architecture with config, routing, responses, validation, JWT, OTP, logging, upload, CORS, and error handling foundations
- Ionic Angular base structure with environments, routing, guards, interceptors, services, models, and reusable components
- Super Admin base structure with session, CSRF, layout, login, dashboard, and assets

## Phase 2 Artifacts

- Idempotent fresh database schema
- Database setup CLI script
- Default Super Admin CLI script
- Repository layer for users, OTPs, settings, auth tokens, and rate limits
- Service layer for settings and token lifecycle
- Database-aware API health endpoint
- Public runtime settings endpoint
- Pagination helper
- JWT token registry and revocation foundation
- Rate-limit persistence foundation

## Phase 3 Artifacts

- Dealer and Installer registration endpoint
- Registration service with validation, normalization, duplicate prevention, user creation, OTP creation, dummy SMS dispatch, and activity logging
- Expanded Ionic registration form with all required identity, bank, password, and terms fields
- Client-side registration validation and API error mapping
- Registration workflow documentation

## Phase 4 Artifacts

- Mobile OTP resend endpoint
- Mobile OTP verification endpoint
- Resend delay enforcement
- OTP attempt limits
- Verification throttling
- Mobile verified timestamp update
- Ionic OTP screen API wiring with countdown and inline errors
- SendPK SMS provider support with `.env` configuration

## Phase 5 Artifacts

- Email OTP resend endpoint
- Email OTP verification endpoint
- SMTP-capable email sending service
- Automatic email OTP send after mobile verification
- Account verification after both mobile and email are confirmed
- Ionic OTP screen support for mobile and email modes

## Phase 6 Artifacts

- Login endpoint
- Logout endpoint
- Authentication service with `password_verify()`
- JWT token issue and revocation
- Login throttling
- Inactive/unverified account blocking
- Ionic login form connected to API
- Dealer/Installer role-based navigation
- AuthGuard and RoleGuard session restoration

## Phase 7 Artifacts

- Dealer dashboard endpoint
- Dealer dashboard service and sales summary queries
- Dealer-only authorization for dashboard data
- Mobile dashboard API integration
- Dashboard loading, refresh, empty, and error states
- Dealer bottom navigation shell

## Phase 8 Artifacts

- Dealer sales CRUD endpoints
- Dealer-only sales authorization
- Sale validation and duplicate serial prevention
- Server-side sales search and pagination
- Sale soft deletion
- Sales activity logging
- Ionic sales list, add/edit form, and detail screens

## Phase 9 Artifacts

- Installer dashboard endpoint
- Installer dashboard service and installation summary queries
- Installer-only authorization for dashboard data
- Mobile Installer dashboard API integration
- Installer dashboard loading, refresh, empty, and error states
- Installer bottom navigation shell

## Phase 10 Artifacts

- Installer installation CRUD endpoints
- Installer-only installation authorization
- Installation validation and soft delete
- Multipart image upload validation and storage
- Upload execution guards
- Ionic installation list, add/edit form, and detail screens
- Capacitor Camera/gallery image selection with preview

## Phase 11 Artifacts

- Installer serial number check endpoint
- Active Dealer sale lookup by device serial
- Matched/unmatched serial response details
- Configurable `require_sale_before_installation` setting
- Installation create/update enforcement when the setting is enabled
- Ionic serial check action on the installation form

## Phase 12 Artifacts

- Super Admin login implementation
- Admin password verification with `password_verify()`
- Admin session creation with session ID regeneration
- Admin logout with session destruction
- CSRF-protected login and logout forms
- Active admin account guard
- Protected admin dashboard route
- Separate admin auth layout without the sidebar
- Admin login/logout activity logging

## Phase 13 Artifacts

- Live Super Admin dashboard service and repository
- Dealer, Installer, sale, and installation totals
- Active and pending account counts
- Today's sales and installation counts
- Matched and unmatched installation serial metrics
- Serial match-rate meter
- Operational status panel for database, serial policy, OTP, and upload settings
- Recent activity feed

## Phase 14 Artifacts

- Super Admin Dealer repository, service, controller, and views
- Dealer list with search, status filter, verification filter, and pagination
- Dealer detail page with identity, contact, bank, and verification fields
- Dealer sales summary and recent sales table
- CSRF-protected activate/deactivate controls
- CSRF-protected verify/unverify controls
- Dealer management activity logging

## Phase 15 Artifacts

- Super Admin Installer repository, service, controller, and views
- Installer list with search, status filter, verification filter, and pagination
- Installer detail page with identity, contact, bank, and verification fields
- Installer installation summary and recent installation table
- Matched and unmatched installation counts per Installer
- CSRF-protected activate/deactivate controls
- CSRF-protected verify/unverify controls
- Installer management activity logging

## Phase 16 Artifacts

- Super Admin Sales repository, service, controller, and views
- Sales list with search, Dealer ID filter, date filters, match filter, and pagination
- Sale detail page with sale, customer, and Dealer context
- Matched installation table for each sale serial number
- Related sales activity feed
- Read-only audit-focused management surface

## Phase 17 Artifacts

- Super Admin Installation repository, service, controller, and views
- Installation list with search, Installer ID filter, date filters, match filter, and pagination
- Installation detail page with customer, Installer, and matched Dealer sale context
- Installation image preview and full image link
- Serial match review from installation to Dealer sale
- Related installation activity feed
- Read-only audit-focused management surface

## Phase 18 Artifacts

- Super Admin Reports repository, service, controller, and view
- Date-bounded sales and installation report summaries
- Serial matched/unmatched report metrics and match-rate meter
- Daily sales and daily installation tables
- Top Dealer and Top Installer report tables
- Unmatched installation review table
- Activity breakdown by role, module, and action

## Phase 19 Artifacts

- Super Admin Settings repository, service, controller, and view
- Editable company name, logo path, support phone, and support email
- Editable OTP expiry, resend delay, and maximum attempt rules
- Editable maximum image upload size
- Editable sale-before-installation serial validation policy
- Settings validation before persistence
- CSRF-protected settings save
- Settings update activity logging

## Phase 20 Artifacts

- Super Admin Activity Logs repository, service, controller, and views
- Searchable activity log listing
- Actor role, module, action, and date range filters
- Paginated audit event browsing
- Activity log detail page with actor, record, IP address, and user agent context
- Admin record links for Dealer, Installer, sale, and installation activity

## Phase 21 Artifacts

- Super Admin profile service, controller, and view
- Authenticated profile details page
- Super Admin name and email update form
- Duplicate admin email validation
- Password change form with current password verification
- Strong password validation and secure password hashing
- Profile and password activity logging

## Phase 22 Artifacts

- Dealer and Installer profile API endpoints
- JWT-protected current profile endpoint
- Mobile profile update endpoint for name, CNIC, and bank details
- Mobile password change endpoint with current password verification
- Duplicate CNIC validation
- Mobile profile service and live Ionic profile screen
- Profile form, password form, loading state, inline validation, toast feedback, and sign out action
- Cached mobile user refresh after profile updates

## Phase 23 Artifacts

- Forgot password API endpoint
- Reset OTP verification API endpoint
- Reset password API endpoint
- Mobile/email reset OTP support based on entered identifier
- Reset OTP throttling, resend wait enforcement, expiry checks, and attempt limits
- Short-lived reset token after OTP verification
- One-time reset token consumption through the verified OTP row
- Password reset with secure hashing and active session revocation
- Mobile forgot password screen with request, verify, and reset steps

## Phase 24 Artifacts

- OTP-protected mobile number change endpoints
- OTP-protected email address change endpoints
- Duplicate mobile and email validation
- Resend wait enforcement, throttling, expiry checks, and attempt limits
- Verified contact update after successful OTP verification
- Mobile profile contact change forms with OTP verification
- Resend countdowns and inline validation for mobile/email changes
- Contact change activity logging

## Phase 25 Artifacts

- Device token storage table for dealer and installer app sessions
- Notification preferences table with default category choices
- JWT-protected device token registration endpoint
- JWT-protected device token removal endpoint
- JWT-protected notification preferences read/update endpoints
- Notification repository, service, and controller
- Device token refresh support with platform, device name, app version, and last-seen tracking
- Notification preference activity logging
- Mobile notification model and API service
- Ionic profile notification preference toggles with save feedback

## Phase 26 Artifacts

- Notification queue table with batch IDs and per-recipient rows
- Super Admin notification repository, service, and controller
- Admin notification broadcast form with audience, channel, category, title, message, and target URL
- Eligible recipient expansion for active verified Dealers and Installers
- Preference-aware broadcast targeting for sales, installation, account, and offer categories
- Push broadcasts limited to users with active device tokens
- Notification batch listing with queued, sent, failed, and cancelled counts
- Notification detail page with broadcast metadata and recipient delivery review
- Admin status actions to mark queued notifications as sent, failed, or cancelled
- Notification activity logging for broadcast and status changes

## Phase 27 Artifacts

- Notification read tracking through `read_at`
- JWT-protected paginated mobile inbox endpoint
- JWT-protected unread notification count endpoint
- JWT-protected notification detail endpoint with automatic read marking
- JWT-protected single notification mark-read endpoint
- JWT-protected bulk mark-all-read endpoint
- Mobile notification inbox model expansion
- Ionic notification inbox module with list and detail pages
- Inbox filters for all, unread, read, and category
- Unread count summary and mark-all-read action
- Dashboard and profile navigation entry points for notifications

## Phase 28 Artifacts

- Notification delivery attempt tracking with attempt count and last-attempt timestamp
- CLI notification worker at `api/console/process-notifications.php`
- Delivery repository for queued notification retrieval and status updates
- Delivery service for push, SMS, and email channels
- General notification sending support in the SMS provider interface
- Dummy SMS notification dispatch for local/dev mode
- SendPK notification SMS dispatch with approved-template configuration
- General notification email dispatch through existing SMTP support
- Push provider interface with log provider and configurable FCM HTTP sender
- Worker JSON summary output for cron and manual runs
- Updated notification/SMS environment examples and console documentation

## Phase 29 Artifacts

- Commission rules table for Dealer sale and Installer installation rates
- Commission earnings table linked to sale and installation source records
- Commission payout table with pending, paid, and cancelled status tracking
- Idempotent commission earning generation from existing sales and installations
- Super Admin commission repository, service, controller, and view
- Editable fixed PKR commission rates
- Commission dashboard statistics for pending, approved, paid, and payout totals
- Earnings list with role, status, date, user, source, and payout context
- Bulk pending earning approval
- Payout creation from approved earnings grouped by user
- Payout status updates with payment reference and notes
- Commission activity logging for rules, generation, approvals, and payouts

## Phase 30 Artifacts

- JWT-protected earnings summary endpoint for Dealers and Installers
- JWT-protected commission earning history endpoint
- JWT-protected payout history endpoint
- User-scoped earnings repository, service, and controller
- Mobile earning and payout models
- Mobile earnings service
- Ionic earnings wallet module and route
- Wallet summary cards for lifetime, approved, pending, and paid totals
- Payout summary cards for pending and paid payouts
- Earnings and payout history views with status filters
- Infinite scroll, refresh, loading, empty, and error states
- Profile navigation entry point for the earnings wallet

## Phase 31 Artifacts

- Native Capacitor Android project under `mobile-app/android`
- Android application ID and namespace configured as `com.stabimatic.app`
- Android app label configured as `STABIMATIC`
- Capacitor Android plugin sync for App, Camera, Device, Network, and Preferences
- Android permissions for internet, network state, camera, image access, and notifications
- Generated Android launcher and splash resources with documented replacement paths for final brand artwork
- Android npm workflow scripts for sync, open, debug run, debug APK, and release APK
- Android build guide with prerequisites, sync steps, Android Studio workflow, release notes, and release checklist

## Phase 32 Artifacts

- Android-specific Angular environment for emulator development
- Angular `android` build and serve configuration
- Android development npm scripts for sync and emulator/device run
- Debug-only Android cleartext HTTP configuration for local API testing
- Android development guide with emulator, physical device, and troubleshooting notes
- Android smoke-test checklist for auth, Dealer, Installer, notifications, and earnings flows

## Phase 33 Artifacts

- Mobile release configuration guard for production API URL validation
- Android release candidate npm workflow
- Optional Android release signing configuration through ignored `keystore.properties`
- Keystore properties example file
- Android keystore and signing secret ignore rules
- Android release candidate guide
- Android QA matrix covering auth, Dealer, Installer, serial, camera/gallery, notifications, earnings, reset, contact change, and logout flows

## Phase 34 Artifacts

- Production API URL update script with HTTPS and host validation
- Android signing validation script
- Signed release npm workflow gated by production API and keystore checks
- Explicit unsigned release packaging command for non-shipping smoke builds
- Android App Bundle release command
- Phase 34 production release documentation

## Phase 35 Artifacts

- Android device QA helper script with ADB auto-discovery
- Android device listing, preflight, install, logs, and full QA npm commands
- ADB timeout handling to avoid stuck QA runs
- Android physical device QA guide
- Manual device QA checklist and result template
- Documented current blocker when no authorized Android device is attached

## Phase 36 Artifacts

- Android store metadata folder structure
- English store listing draft
- Android data safety worksheet
- Version 1.0.0 release notes
- Android handoff checklist
- Android QA result template
- Android screenshots holding folder
- Android store handoff readiness checker
- Android store handoff documentation

## Phase 37 Artifacts

- Production API environment example
- Production admin environment example
- API production environment validator
- Admin production environment validator
- Mobile npm commands for API, admin, and combined production deployment validation
- Production API deployment documentation
- Deployed API health-check route documentation
- Android production API URL configuration workflow

## Environment Setup

Copy environment examples before running:

```bash
cp api/.env.example api/.env
cp admin/.env.example admin/.env
```

Update secrets, database credentials, SMTP settings, and domain URLs for your environment.

For production deployments, start from:

```bash
cp api/.env.production.example api/.env
cp admin/.env.production.example admin/.env
```

Then validate the deployment configuration:

```bash
cd mobile-app
npm run deploy:production:check
```

## Next Phase

PHASE 38 - Server Deployment Package

Phase 38 should prepare the server deployment package, database import checklist, Apache rewrite verification, and notification worker scheduling notes.
