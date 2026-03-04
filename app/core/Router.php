<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    public function dispatch(string $controllerName, string $actionName): void
    {
        $controllerClass = '\\App\\Controllers\\' . ucfirst($controllerName) . 'Controller';
        $actionMethod = $actionName . 'Action';

        if (!class_exists($controllerClass)) {
            http_response_code(404);
            echo 'Không tìm thấy controller: ' . htmlspecialchars($controllerName, ENT_QUOTES, 'UTF-8');
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $actionMethod)) {
            http_response_code(404);
            echo 'Không tìm thấy action: ' . htmlspecialchars($actionName, ENT_QUOTES, 'UTF-8');
            return;
        }

        $controller->{$actionMethod}();
    }
}

