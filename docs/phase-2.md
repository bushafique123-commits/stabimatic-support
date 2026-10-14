# Phase 2 - Database & Core API Foundation

## Completed Scope

- Made the SQL schema safe for fresh setup with `CREATE TABLE IF NOT EXISTS`.
- Added security foundation tables:
  - `auth_tokens` for JWT token IDs, logout, revocation, and future session invalidation.
  - `rate_limits` for OTP resend throttling and login brute-force protection.
- Added `database/setup_database.php` to apply `database/schema.sql` from `api/.env`.
- Added repository classes for users, OTPs, settings, auth tokens, and rate limits.
- Added service classes for settings and token lifecycle.
- Added paginator helper for server-side pagination responses.
- Added database-aware health controller.
- Added public foundation settings endpoint for non-sensitive mobile configuration.
- Removed live placeholder routes; planned business endpoints remain documented and will be registered as each phase implements them.

## Fresh Setup Commands

```bash
copy api\.env.example api\.env
php database/setup_database.php
php database/create_super_admin.php "Super Admin" admin@example.com "StrongPassword123!"
```

## Foundation Endpoints

| Method | URL | Purpose |
| --- | --- | --- |
| GET | `/api/v1/health` | API and database readiness |
| GET | `/api/v1/settings/public` | Non-sensitive runtime settings |

## Notes For Phase 3

Registration should use `UserRepository`, `OtpVerificationRepository`, `OtpService`, `RateLimitRepository`, and `Logger::activity`. The API must continue deriving authenticated user identity from JWT claims and token registry state.
