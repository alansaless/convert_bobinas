<?php
// Cliente para RD Station (OAuth2 + Contacts API)
require_once __DIR__ . '/includes/bootstrap.php';

function rd_token_cache_path(): string {
    return __DIR__ . '/storage/token.json';
}

function rd_get_access_token(): ?string {
    $cacheFile = rd_token_cache_path();
    if (file_exists($cacheFile)) {
        $cache = json_decode(@file_get_contents($cacheFile), true);
        if ($cache && isset($cache['access_token'], $cache['expires_at']) && time() < $cache['expires_at']) {
            return $cache['access_token'];
        }
    }

    $client_id = env('RD_CLIENT_ID');
    $client_secret = env('RD_CLIENT_SECRET');
    $refresh_token = env('RD_REFRESH_TOKEN');
    if (!$client_id || !$client_secret || !$refresh_token) {
        app_log('RD: credenciais ausentes');
        return null;
    }

    $url = 'https://api.rd.services/auth/token';
    $payload = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'refresh_token' => $refresh_token,
        'grant_type' => 'refresh_token',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 300 && $res) {
        $data = json_decode($res, true);
        $access = $data['access_token'] ?? null;
        $expires_in = $data['expires_in'] ?? 1200; // 20 minutos
        if ($access) {
            @file_put_contents($cacheFile, json_encode([
                'access_token' => $access,
                'expires_at' => time() + (int)$expires_in - 30,
            ]));
            return $access;
        }
    }
    app_log('RD: falha ao obter token (' . $code . ') ' . $res);
    return null;
}

function rd_contacts_endpoint(): string {
    return env('RD_CONTACTS_ENDPOINT', 'https://api.rd.services/platform/contacts');
}

function rd_api_request(string $method, string $url, array $body = null): array {
    $token = rd_get_access_token();
    if (!$token) {
        return ['status' => 0, 'body' => null];
    }
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $code, 'body' => $res];
}

function rd_synthetic_email_for(array $lead): ?string {
    $allow = filter_var(env('RD_ALLOW_SYNTHETIC_EMAIL', 'true'), FILTER_VALIDATE_BOOLEAN);
    if (!$allow) return null;
    $phone = isset($lead['phone']) ? preg_replace('/\D+/', '', (string)$lead['phone']) : null;
    if ($phone) return $phone . '@prospecta.local';
    $name = $lead['name'] ?? null;
    if ($name) return strtolower(preg_replace('/\s+/', '.', preg_replace('/[^a-zA-Z0-9\s]/', '', $name))) . '@prospecta.local';
    return null;
}

function rd_upsert_contact(PDO $pdo, array $lead): array {
    $endpoint = rd_contacts_endpoint();
    $email = $lead['email'] ?? null;
    if (!$email) {
        $email = rd_synthetic_email_for($lead);
    }
    $payload = [
        'emails' => $email ? [['email' => $email]] : [],
        'name' => $lead['name'] ?? null,
        'personal_phone' => $lead['phone'] ?? null,
        'website' => $lead['website'] ?? null,
        'city' => $lead['city'] ?? null,
        'state' => $lead['state'] ?? null,
        'country' => $lead['country'] ?? null,
        // Campos adicionais podem ser mapeados conforme necessidade
    ];

    // Tenta criar
    $create = rd_api_request('POST', $endpoint, $payload);
    log_rd_event($pdo, $lead['id'] ?? null, 'create', $create['status'], $create['body']);
    if ($create['status'] >= 200 && $create['status'] < 300) {
        $data = json_decode($create['body'], true);
        return ['ok' => true, 'uuid' => $data['uuid'] ?? null, 'status' => $create['status']];
    }

    // Se já existe, tenta atualizar (PATCH)
    if ($create['status'] === 409 || $create['status'] === 422) {
        $update = rd_api_request('PATCH', $endpoint, $payload);
        log_rd_event($pdo, $lead['id'] ?? null, 'update', $update['status'], $update['body']);
        if ($update['status'] >= 200 && $update['status'] < 300) {
            $data = json_decode($update['body'], true);
            return ['ok' => true, 'uuid' => $data['uuid'] ?? null, 'status' => $update['status']];
        }
        return ['ok' => false, 'error' => $update['body'] ?? null, 'status' => $update['status']];
    }
    return ['ok' => false, 'error' => $create['body'] ?? null, 'status' => $create['status']];
}