<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    echo "Notification delivery can only run from CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../core/Env.php';
require_once __DIR__ . '/../core/ExceptionHandler.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/BaseRepository.php';
require_once __DIR__ . '/../repositories/NotificationDeliveryRepository.php';
require_once __DIR__ . '/../services/Logger.php';
require_once __DIR__ . '/../services/SmsProviderInterface.php';
require_once __DIR__ . '/../services/DummySmsProvider.php';
require_once __DIR__ . '/../services/SendPkSmsProvider.php';
require_once __DIR__ . '/../services/SmsProviderFactory.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../services/PushProviderInterface.php';
require_once __DIR__ . '/../services/LogPushProvider.php';
require_once __DIR__ . '/../services/FcmPushProvider.php';
require_once __DIR__ . '/../services/PushProviderFactory.php';
require_once __DIR__ . '/../services/NotificationDeliveryService.php';

Env::load(__DIR__ . '/../.env');
ExceptionHandler::register((bool) config('app.debug', false));

$options = getopt('', ['limit::']);
$limit = isset($options['limit'])
    ? (int) $options['limit']
    : (int) config('notifications.delivery_batch_size', 50);

try {
    $result = (new NotificationDeliveryService())->process($limit);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    Logger::error('Notification worker crashed', ['detail' => $exception->getMessage()]);
    echo json_encode([
        'ok' => false,
        'message' => 'Notification worker failed',
        'error' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}
