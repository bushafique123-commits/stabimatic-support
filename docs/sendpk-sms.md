# SendPK SMS Provider

The STABIMATIC API can send mobile OTPs through SendPK.

## Required Settings

Set these in `api/.env`:

```env
SMS_PROVIDER=sendpk
SENDPK_API_URL=https://sendpk.com/api/sms.php
SENDPK_API_KEY=your-sendpk-api-key
SENDPK_SENDER=8584
SENDPK_OTP_TEMPLATE_ID=your-approved-template-id
SENDPK_OTP_MESSAGE_TEMPLATE={"code":"{otp}","contact":"your-support-contact"}
SENDPK_NOTIFICATION_TEMPLATE_ID=your-approved-notification-template-id
SENDPK_NOTIFICATION_MESSAGE_TEMPLATE={"title":"{title}","message":"{message}"}
SENDPK_TIMEOUT_SECONDS=15
```

The local `api/.env` file is ignored by git and may contain the real API key. Do not put the real key in `.env.example`, source code, the Ionic mobile app, or admin JavaScript.

## Browser and Android CORS

For browser preview and Android testing, the hosted API must allow the app origins in `api/.env`:

```env
CORS_ALLOWED_ORIGINS=http://localhost:8100,http://localhost,http://localhost:4321,http://127.0.0.1:4321,capacitor://localhost,https://localhost,http://stabimatic.techxaz.com,http://stabimatic.techxaz.com:8100
```

If registration fails before reaching the API, the mobile app will show a connection/CORS error. Copy this line to the hosted cPanel `.env` after uploading API changes.

## Template ID

SendPK fixed/OTP SMS requires an approved template ID. Get it from the SendPK dashboard under Fixed-SMS templates or through the template listing endpoint.

`SENDPK_OTP_MESSAGE_TEMPLATE` must match the variables in that approved template. For the approved template:

```text
Your OTP code for YOUR-APP-NAME is #code#
For any issues, contact us at #contact
```

use:

```env
SENDPK_OTP_MESSAGE_TEMPLATE={"code":"{otp}","contact":"your-support-contact"}
```

For a template such as `Hello #name#, your OTP code is #otp#`, use:

```env
SENDPK_OTP_MESSAGE_TEMPLATE={"name":"STABIMATIC","otp":"{otp}"}
```

Notification broadcasts use `SENDPK_NOTIFICATION_TEMPLATE_ID` and `SENDPK_NOTIFICATION_MESSAGE_TEMPLATE`. For a template such as `#title#: #message#`, use:

```env
SENDPK_NOTIFICATION_MESSAGE_TEMPLATE={"title":"{title}","message":"{message}"}
```

## Runtime Behavior

- Mobile numbers are converted from `03XXXXXXXXX` to `923XXXXXXXXX` before calling SendPK.
- OTP messages are sent from the backend only.
- Notification SMS messages are sent by the CLI delivery worker.
- API keys are never returned to the mobile app.
- A failed SendPK response returns a clean API error and does not silently report OTP success.
