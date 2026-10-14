# Production API Deployment Configuration

## Environment Files

Use these templates for production deployment:

```text
api/.env.production.example
admin/.env.production.example
```

Deploy them as:

```text
api/.env
admin/.env
```

Never commit real `.env` files, database passwords, JWT secrets, SMS keys, SMTP passwords, FCM keys, or signing credentials.

## API Validation

```bash
cd mobile-app
npm run deploy:api:check
```

The API validator checks:

- Production environment mode.
- Debug mode disabled.
- HTTPS `APP_URL` and `API_URL`.
- Database credentials.
- Strong JWT secret.
- Production-safe CORS origins.
- OTP exposure disabled.
- SendPK provider, key, sender, and approved template IDs.
- FCM push provider and server key.
- SMTP settings.
- Upload size and upload path.

## Admin Validation

```bash
cd mobile-app
npm run deploy:admin:check
```

The admin validator checks:

- Production environment mode.
- Debug mode disabled.
- HTTPS `APP_URL` and `ADMIN_URL`.
- Database credentials.
- Secure admin session cookies.
- Session storage path.

## Combined Validation

```bash
cd mobile-app
npm run deploy:production:check
```

This runs the API validator, admin validator, and Android production API guard.
It reports all blockers in one pass instead of stopping at the first failed check.

## Health Check

After deployment, verify the API health route:

```text
GET https://your-domain.example/stabimatic/api/v1/health
```

Expected response shape:

```json
{
  "success": true,
  "message": "STABIMATIC API is running",
  "data": {
    "service": "stabimatic-api",
    "version": "v1",
    "environment": "production",
    "database": "ok"
  }
}
```

## Android Production URL

Set the mobile production API target after the deployed API URL is known:

```bash
cd mobile-app
npm run release:set-api -- https://your-domain.example/stabimatic/api/v1
```

Then verify:

```bash
npm run release:check
```

## Production Service Checklist

- MySQL database and user created.
- Database schema imported.
- API `.env` deployed from the production example.
- Admin `.env` deployed from the production example.
- API uploads directory is writable.
- Admin session directory is writable.
- SendPK API key configured.
- SendPK approved OTP template ID configured.
- SendPK approved notification template ID configured.
- SMTP host, username, password, and from email configured.
- FCM provider configured if push notifications are enabled.
- Notification worker scheduled with `api/console/process-notifications.php`.
- Android `environment.prod.ts` points to the deployed API `/v1` URL.
- Production API health route returns `database: ok`.
