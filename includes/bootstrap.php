<?php
// Bootstrap comum para scripts PHP
require_once __DIR__ . '/env.php';
date_default_timezone_set(app_timezone());
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logger.php';

cors_headers();
if (is_options_preflight()) {
    http_response_code(204);
    exit;
}

// Garante diretórios de storage
ensure_storage();