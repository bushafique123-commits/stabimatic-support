# Phase 28 - Notification Delivery Worker

## Completed Scope

- Added delivery attempt tracking to the `notifications` table:
  - `attempt_count`
  - `last_attempt_at`
- Added `NotificationDeliveryRepository`.
- Added `NotificationDeliveryService`.
- Added CLI worker:
  - `php api/console/process-notifications.php --limit=50`
- Added general notification sending support to `SmsProviderInterface`.
- Added dummy SMS notification dispatch for local development.
- Added SendPK notification SMS dispatch with separate approved-template settings.
- Added `EmailService::sendNotification()`.
- Added push provider foundation:
  - `PushProviderInterface`
  - `LogPushProvider`
  - `FcmPushProvider`
  - `PushProviderFactory`
- Added worker processing for queued push, SMS, and email rows.
- Added sent and failed status updates after provider dispatch.
- Added provider failure reasons to `error_message`.
- Added JSON worker summary output for operational monitoring.

## Console Command

```bash
php api/console/process-notifications.php --limit=50
```

The worker loads queued notification rows, sends each row through the configured provider, and updates each notification to `sent` or `failed`.

## Environment Settings

```env
PUSH_PROVIDER=log
FCM_ENDPOINT=https://fcm.googleapis.com/fcm/send
FCM_SERVER_KEY=
FCM_TIMEOUT_SECONDS=15
NOTIFICATION_DELIVERY_BATCH_SIZE=50

SENDPK_NOTIFICATION_TEMPLATE_ID=
SENDPK_NOTIFICATION_MESSAGE_TEMPLATE={"title":"{title}","message":"{message}"}
```

## Deferred To Phase 29

- Dealer/Installer commission rules.
- Earnings calculation.
- Payout tracking.
