<?php

declare(strict_types=1);

// Router for PHP's built-in web server, used by CurlTransportTest.
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : null;
header('Content-Type: application/json');

switch ($path) {
    case '/echo':
        echo json_encode([
            'method' => $_SERVER['REQUEST_METHOD'],
            'authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
            'body' => file_get_contents('php://input'),
        ]);
        break;
    case '/401':
        http_response_code(401);
        echo '{"message":"Unauthorized"}';
        break;
    case '/503':
        http_response_code(503);
        echo 'Service Unavailable';
        break;
    case '/slow':
        sleep(3);
        echo '{}';
        break;
}
