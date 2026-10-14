<?php

$router->get('/health', new HealthController());
$router->get('/settings/public', [new FoundationController(), 'settings']);
$router->post('/auth/register', function (Request $request): void {
    (new AuthController())->register($request);
});
$router->post('/auth/send-mobile-otp', function (Request $request): void {
    (new AuthController())->sendMobileOtp($request);
});
$router->post('/auth/verify-mobile-otp', function (Request $request): void {
    (new AuthController())->verifyMobileOtp($request);
});
$router->post('/auth/send-email-otp', function (Request $request): void {
    (new AuthController())->sendEmailOtp($request);
});
$router->post('/auth/verify-email-otp', function (Request $request): void {
    (new AuthController())->verifyEmailOtp($request);
});
$router->post('/auth/login', function (Request $request): void {
    (new AuthController())->login($request);
});
$router->post('/auth/logout', function (Request $request): void {
    (new AuthController())->logout($request);
});
$router->post('/auth/forgot-password', function (Request $request): void {
    (new AuthController())->forgotPassword($request);
});
$router->post('/auth/verify-reset-otp', function (Request $request): void {
    (new AuthController())->verifyResetOtp($request);
});
$router->post('/auth/reset-password', function (Request $request): void {
    (new AuthController())->resetPassword($request);
});
$router->get('/profile', function (Request $request): void {
    (new ProfileController())->show($request);
});
$router->put('/profile', function (Request $request): void {
    (new ProfileController())->update($request);
});
$router->post('/profile/change-password', function (Request $request): void {
    (new ProfileController())->changePassword($request);
});
$router->post('/profile/change-mobile/send-otp', function (Request $request): void {
    (new ProfileController())->sendMobileChangeOtp($request);
});
$router->post('/profile/change-mobile/verify', function (Request $request): void {
    (new ProfileController())->verifyMobileChangeOtp($request);
});
$router->post('/profile/change-email/send-otp', function (Request $request): void {
    (new ProfileController())->sendEmailChangeOtp($request);
});
$router->post('/profile/change-email/verify', function (Request $request): void {
    (new ProfileController())->verifyEmailChangeOtp($request);
});
$router->post('/notifications/device-tokens', function (Request $request): void {
    (new NotificationController())->registerDevice($request);
});
$router->delete('/notifications/device-tokens', function (Request $request): void {
    (new NotificationController())->removeDevice($request);
});
$router->get('/notifications/preferences', function (Request $request): void {
    (new NotificationController())->preferences($request);
});
$router->put('/notifications/preferences', function (Request $request): void {
    (new NotificationController())->updatePreferences($request);
});
$router->get('/notifications', function (Request $request): void {
    (new NotificationController())->inbox($request);
});
$router->get('/notifications/unread-count', function (Request $request): void {
    (new NotificationController())->unreadCount($request);
});
$router->get('/notifications/{id}', function (Request $request, string $id): void {
    (new NotificationController())->show($request, $id);
});
$router->post('/notifications/{id}/read', function (Request $request, string $id): void {
    (new NotificationController())->markRead($request, $id);
});
$router->post('/notifications/read-all', function (Request $request): void {
    (new NotificationController())->markAllRead($request);
});
$router->get('/earnings/summary', function (Request $request): void {
    (new EarningsController())->summary($request);
});
$router->get('/earnings', function (Request $request): void {
    (new EarningsController())->earnings($request);
});
$router->get('/earnings/payouts', function (Request $request): void {
    (new EarningsController())->payouts($request);
});
$router->get('/dealer/dashboard', function (Request $request): void {
    (new DealerDashboardController())->index($request);
});
$router->get('/installer/dashboard', function (Request $request): void {
    (new InstallerDashboardController())->index($request);
});
$router->get('/sales', function (Request $request): void {
    (new SalesController())->index($request);
});
$router->get('/sales/commission-lookup', function (Request $request): void {
    (new SalesController())->commissionLookup($request);
});
$router->get('/sales/{id}', function (Request $request, string $id): void {
    (new SalesController())->show($request, $id);
});
$router->post('/sales', function (Request $request): void {
    (new SalesController())->store($request);
});
$router->put('/sales/{id}', function (Request $request, string $id): void {
    (new SalesController())->update($request, $id);
});
$router->delete('/sales/{id}', function (Request $request, string $id): void {
    (new SalesController())->destroy($request, $id);
});
$router->get('/installations', function (Request $request): void {
    (new InstallationsController())->index($request);
});
$router->get('/installations/check-serial/{serial}', function (Request $request, string $serial): void {
    (new InstallationsController())->checkSerial($request, $serial);
});
$router->get('/installations/check-commission', function (Request $request): void {
    (new InstallationsController())->checkCommission($request);
});
$router->get('/installations/commission-lookup', function (Request $request): void {
    (new InstallationsController())->commissionLookup($request);
});
$router->get('/installations/{id}', function (Request $request, string $id): void {
    (new InstallationsController())->show($request, $id);
});
$router->post('/installations', function (Request $request): void {
    (new InstallationsController())->store($request);
});
$router->put('/installations/{id}', function (Request $request, string $id): void {
    (new InstallationsController())->update($request, $id);
});
$router->delete('/installations/{id}', function (Request $request, string $id): void {
    (new InstallationsController())->destroy($request, $id);
});
