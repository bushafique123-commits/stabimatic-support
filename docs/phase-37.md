# Phase 37 - Production API Deployment Configuration

## Completed Scope

- Added production API environment example.
- Added production admin environment example.
- Added API production environment validator.
- Added admin production environment validator.
- Added npm deployment validation commands from the mobile workspace.
- Added combined production deployment check.
- Added production API deployment documentation.
- Documented the deployed API health-check route.
- Documented Android production API URL configuration.
- Updated README through Phase 37.

## Validation Commands

```bash
cd mobile-app
npm run deploy:api:check
npm run deploy:admin:check
npm run deploy:production:check
```

## Production API URL Command

```bash
cd mobile-app
npm run release:set-api -- https://your-domain.example/stabimatic/api/v1
```

## Current Production Blockers

- API `.env` is not yet production-safe.
- Admin `.env` is not yet production-safe.
- Android production API URL still uses the placeholder.
- SendPK template IDs still need production approved values.
- SMTP and FCM production credentials still need configured values.
- Android signing keystore is still not configured.

## Deferred To Phase 38

- Server deployment package preparation.
- Database migration/import checklist.
- Apache rewrite and document-root verification.
- Production worker scheduling notes.
