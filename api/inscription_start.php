<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

file_put_contents(__DIR__ . '/../inscription_mode.txt', '1');
file_put_contents(__DIR__ . '/../inscription_uid.txt',  '');

echo json_encode(['ok' => true]);
