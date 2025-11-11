<?php
// Funções utilitárias para respostas JSON e entrada

function json_response($data, int $status = 200): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function get_json_input(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function request_header(string $name, $default = null) {
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return $_SERVER[$key] ?? $default;
}

function cors_headers(): void {
    $origins = env('CORS_ALLOWED_ORIGINS', '*');
    header('Access-Control-Allow-Origin: ' . $origins);
    header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
}

function is_options_preflight(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS';
}

function sanitize_str(?string $s): ?string {
    if ($s === null) return null;
    $s = trim($s);
    return $s !== '' ? $s : null;
}

function now(): string { return date('Y-m-d H:i:s'); }