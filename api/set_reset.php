<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$file = __DIR__ . '/../reset_ordre.txt';

if (file_put_contents($file, '1') !== false) {
    echo 'OK';
} else {
    http_response_code(500);
    echo 'ERR';
}
