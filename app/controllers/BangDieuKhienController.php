<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use PDO;

class BangDieuKhienController extends BaseController
{
    public function indexAction(): void
    {
        $this->requireAdmin();

        $pdo = Database::getConnection();

        $studentCount = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
        $invoicePending = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'pending'")->fetchColumn();
        $invoicePaid = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'paid'")->fetchColumn();

        $this->render('bangdieukhien/index', [
            'pageTitle' => 'Bảng điều khiển',
            'studentCount' => $studentCount,
            'invoicePending' => $invoicePending,
            'invoicePaid' => $invoicePaid,
        ]);
    }
}

