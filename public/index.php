<?php
declare(strict_types=1);

use App\Core\Router;

session_start();

require_once __DIR__ . '/../app/core/Config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/BaseController.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/helpers/number_to_words.php';
require_once __DIR__ . '/../app/helpers/vietqr.php';

// Autoload rất đơn giản cho namespace App\Controllers và App\Models
spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'App\\') !== 0) {
        return;
    }

    $relative = str_replace('App\\', '', $class);
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);

    $file = __DIR__ . '/../app/' . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

$router = new Router();

// Định tuyến dạng ?controller=&action=, mặc định dashboard/index
$controller = $_GET['controller'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

try {
    $router->dispatch($controller, $action);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>Lỗi hệ thống</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}

