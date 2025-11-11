<?php
// Router para servidor embutido do PHP (php -S) que reproduz regras do .htaccess
// Permite acessar URLs como /api/... e /webhook/lead sem Apache.

// Servir arquivos estáticos diretamente quando existirem
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if (is_file($file)) {
        return false; // deixa o servidor embutido servir o arquivo
    }
}

// Normaliza REQUEST_URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Roteia API: /api/(...)
if (preg_match('#^/api/(.*)$#', $uri, $m)) {
    $_GET['path'] = $m[1];
    require __DIR__ . '/api.php';
    exit;
}

// Roteia Webhook: /webhook/lead
if ($uri === '/webhook/lead') {
    $_GET['path'] = 'lead';
    require __DIR__ . '/webhook.php';
    exit;
}

// Fallback: index
require __DIR__ . '/index.php';
exit;