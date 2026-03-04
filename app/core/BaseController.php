<?php
declare(strict_types=1);

namespace App\Core;

abstract class BaseController
{
    protected function render(string $view, array $params = []): void
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View không tồn tại: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($params, EXTR_SKIP);

        $baseUrl = Config::baseUrl();

        // layout chính
        $contentView = $viewFile;
        require __DIR__ . '/../views/layouts/chinh.php';
    }

    protected function renderPlain(string $view, array $params = []): void
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View không tồn tại: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($params, EXTR_SKIP);
        require $viewFile;
    }

    protected function renderPrint(string $view, array $params = []): void
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View không tồn tại: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($params, EXTR_SKIP);
        
        $pageTitle = $params['pageTitle'] ?? '';
        require __DIR__ . '/../views/layouts/in.php';
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function requireLogin(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('index.php?controller=auth&action=login');
        }
    }

    protected function requireAdmin(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('index.php?controller=auth&action=login');
        }
        if (($_SESSION['user_role'] ?? 'staff') !== 'admin') {
            http_response_code(403);
            echo 'Bạn không có quyền truy cập trang này.';
            exit;
        }
    }
}

