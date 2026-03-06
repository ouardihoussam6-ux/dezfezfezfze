<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Badge.php';

header('Content-Type: text/plain; charset=utf-8');

$uid = strtoupper(trim($_POST['uid'] ?? ''));

if ($uid === '') {
    echo 'REFUSE';
    exit;
}

try {
    echo Badge::isAuthorized($uid) ? 'OK' : 'REFUSE';
} catch (Throwable) {
    echo 'REFUSE';
}
