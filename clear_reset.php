<?php
declare(strict_types=1);

// Point d'entrée ESP32 : GET → remet reset_ordre.txt à "0"
header('Content-Type: text/plain; charset=utf-8');

$file = __DIR__ . '/reset_ordre.txt';

if (file_put_contents($file, '0') !== false) {
    echo 'OK';
} else {
    http_response_code(500); echo 'ERR';
}
