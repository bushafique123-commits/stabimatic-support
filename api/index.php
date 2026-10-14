<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

require_once __DIR__ . '/core/Env.php';
require_once __DIR__ . '/core/ExceptionHandler.php';
require_once __DIR__ . '/core/Request.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/Validator.php';
require_once __DIR__ . '/helpers/InputNormalizer.php';
require_once __DIR__ . '/helpers/JwtHelper.php';
require_once __DIR__ . '/helpers/FileUpload.php';
require_once __DIR__ . '/helpers/Paginator.php';
require_once __DIR__ . '/repositories/BaseRepository.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/OtpVerificationRepository.php';
require_once __DIR__ . '/repositories/SettingRepository.php';
require_once __DIR__ . '/repositories/AuthTokenRepository.php';
require_once __DIR__ . '/repositories/RateLimitRepository.php';
require_once __DIR__ . '/repositories/CommissionImportRepository.php';
require_once __DIR__ . '/repositories/SalesRepository.php';
require_once __DIR__ . '/repositories/InstallationsRepository.php';
require_once __DIR__ . '/repositories/NotificationRepository.php';
require_once __DIR__ . '/repositories/EarningsRepository.php';
require_once __DIR__ . '/services/Logger.php';
require_once __DIR__ . '/services/SmsProviderInterface.php';
require_once __DIR__ . '/services/DummySmsProvider.php';
require_once __DIR__ . '/services/SendPkSmsProvider.php';
require_once __DIR__ . '/services/SmsProviderFactory.php';
require_once __DIR__ . '/services/OtpService.php';
require_once __DIR__ . '/services/EmailService.php';
require_once __DIR__ . '/services/SettingsService.php';
require_once __DIR__ . '/services/TokenService.php';
require_once __DIR__ . '/services/RegistrationService.php';
require_once __DIR__ . '/services/MobileOtpService.php';
require_once __DIR__ . '/services/EmailOtpService.php';
require_once __DIR__ . '/services/AuthenticationService.php';
require_once __DIR__ . '/services/PasswordResetService.php';
require_once __DIR__ . '/services/DealerDashboardService.php';
require_once __DIR__ . '/services/SalesService.php';
require_once __DIR__ . '/services/InstallerDashboardService.php';
require_once __DIR__ . '/services/InstallationsService.php';
require_once __DIR__ . '/services/ProfileService.php';
require_once __DIR__ . '/services/ContactChangeService.php';
require_once __DIR__ . '/services/NotificationService.php';
require_once __DIR__ . '/services/EarningsService.php';
require_once __DIR__ . '/middleware/CorsMiddleware.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/RoleMiddleware.php';
require_once __DIR__ . '/controllers/HealthController.php';
require_once __DIR__ . '/controllers/FoundationController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DealerDashboardController.php';
require_once __DIR__ . '/controllers/SalesController.php';
require_once __DIR__ . '/controllers/InstallerDashboardController.php';
require_once __DIR__ . '/controllers/InstallationsController.php';
require_once __DIR__ . '/controllers/ProfileController.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/EarningsController.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

Env::load(__DIR__ . '/.env');
ExceptionHandler::register((bool) config('app.debug', false));
CorsMiddleware::handle(config('cors.allowed_origins', []));

$router = new Router(['/v1', '/api/v1']);
require __DIR__ . '/routes/v1.php';

$router->dispatch(Request::capture());
