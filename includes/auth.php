<?php
// Validação simples de API Key via header X-Api-Key

function require_api_key(): void {
    $incoming = request_header('X-Api-Key');
    $expected = env('API_KEY');
    if (!$expected || !$incoming || !hash_equals($expected, $incoming)) {
        json_response(['error' => 'unauthorized'], 401);
    }
}