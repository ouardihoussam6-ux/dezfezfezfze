<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/../inscription_uid.txt';
$uid  = trim((string) (file_get_contents($file) ?: ''));

if ($uid !== '') {
    file_put_contents($file, '');
    echo json_encode(['uid' => $uid]);
} else {
    echo json_encode(['uid' => null]);
}
