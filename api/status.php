<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Place.php';
require_once __DIR__ . '/../models/Log.php';

header('Content-Type: application/json; charset=utf-8');

try {
    echo json_encode([
        'places' => Place::all(),
        'stats'  => Place::stats(),
        'recent' => Log::recent(10),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
