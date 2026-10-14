# STABIMATIC Database

## Fresh Setup

1. Copy `api/.env.example` to `api/.env`.
2. Update database credentials.
3. Run:

```bash
php database/setup_database.php
```

4. Create or update the default Super Admin:

```bash
php database/create_super_admin.php "Super Admin" admin@example.com "StrongPassword123!"
```

## Schema Notes

- `users.role` is limited to `dealer` and `installer`.
- Admin accounts live separately in `admin_users`.
- OTP values are designed to be stored as password hashes.
- `sales` and `installations` use `deleted_at` soft deletion.
- Active sale serial uniqueness is enforced by `sales.active_device_serial`.
- `auth_tokens` supports JWT token IDs, logout, revocation, and future session invalidation.
- `rate_limits` supports brute-force and resend throttling in later auth modules.

## Future Schema Changes

Before changing schema after this phase, add a migration or ALTER statement to `database/migrations/` and document the reason.
