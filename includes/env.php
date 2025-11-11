<?php
// Carregador simples de variáveis de ambiente a partir de .env
// Uso: $value = env('DB_HOST', '127.0.0.1');

function load_env_file(string $path): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $vars = [];
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $vars[trim($parts[0])] = trim($parts[1]);
            }
        }
    }
    $cache = $vars;
    return $vars;
}

function env(string $key, $default = null) {
    static $vars = null;
    if ($vars === null) {
        $vars = load_env_file(__DIR__ . '/../.env');
    }
    if (isset($vars[$key])) return $vars[$key];
    $val = getenv($key);
    return $val !== false ? $val : $default;
}

function app_timezone(): string {
    return env('APP_TIMEZONE', 'UTC');
}