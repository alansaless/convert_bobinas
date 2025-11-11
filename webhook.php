<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/rd_client.php';

$path = $_GET['path'] ?? '';
if ($path !== 'lead') {
    json_response(['error' => 'not_found'], 404);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

require_api_key();

$payload = get_json_input();

// Validação mínima (name ou website ou phone)
$name = sanitize_str($payload['name'] ?? null);
$website = sanitize_str($payload['website'] ?? null);
$phone = sanitize_str($payload['phone'] ?? null);
$email = sanitize_str($payload['email'] ?? null);
if (!$name && !$website && !$phone) {
    json_response(['error' => 'invalid_payload', 'message' => 'Requer name, website ou phone'], 422);
}

$pdo = db();

// Deduplicação por phone, website, ou email
$stmt = $pdo->prepare('SELECT * FROM leads WHERE (phone = ? AND phone IS NOT NULL) OR (website = ? AND website IS NOT NULL) OR (email = ? AND email IS NOT NULL) LIMIT 1');
$stmt->execute([$phone, $website, $email]);
$existing = $stmt->fetch();

$data = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'whatsapp' => sanitize_str($payload['whatsapp'] ?? null),
    'address' => sanitize_str($payload['address'] ?? null),
    'city' => sanitize_str($payload['city'] ?? null),
    'state' => sanitize_str($payload['state'] ?? null),
    'country' => sanitize_str($payload['country'] ?? null),
    'lat' => $payload['lat'] ?? null,
    'lng' => $payload['lng'] ?? null,
    'website' => $website,
    'rating' => $payload['rating'] ?? null,
    'reviews_count' => $payload['reviews_count'] ?? null,
    'category' => sanitize_str($payload['category'] ?? null),
    'source' => sanitize_str($payload['source'] ?? 'webhook'),
];

if ($existing) {
    // Atualiza lead existente
    $stmt = $pdo->prepare('UPDATE leads SET name=?, email=?, phone=?, whatsapp=?, address=?, city=?, state=?, country=?, lat=?, lng=?, website=?, rating=?, reviews_count=?, category=?, source=?, raw_payload=?, updated_at=NOW() WHERE id=?');
    $stmt->execute([
        $data['name'], $data['email'], $data['phone'], $data['whatsapp'], $data['address'], $data['city'], $data['state'], $data['country'], $data['lat'], $data['lng'], $data['website'], $data['rating'], $data['reviews_count'], $data['category'], $data['source'], json_encode($payload, JSON_UNESCAPED_UNICODE), $existing['id']
    ]);
    $lead_id = (int)$existing['id'];
} else {
    // Insere novo lead
    $stmt = $pdo->prepare('INSERT INTO leads (name, email, phone, whatsapp, address, city, state, country, lat, lng, website, rating, reviews_count, category, source, raw_payload) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $data['name'], $data['email'], $data['phone'], $data['whatsapp'], $data['address'], $data['city'], $data['state'], $data['country'], $data['lat'], $data['lng'], $data['website'], $data['rating'], $data['reviews_count'], $data['category'], $data['source'], json_encode($payload, JSON_UNESCAPED_UNICODE)
    ]);
    $lead_id = (int)$pdo->lastInsertId();
}

// Tenta integrar com RD Station
$lead_for_rd = $data;
$lead_for_rd['id'] = $lead_id;
$rd = rd_upsert_contact($pdo, $lead_for_rd);
if (($rd['ok'] ?? false) && ($rd['uuid'] ?? null)) {
    $stmt = $pdo->prepare('UPDATE leads SET rd_uuid=? WHERE id=?');
    $stmt->execute([$rd['uuid'], $lead_id]);
} else {
    // Marca para reprocessamento
    $stmt = $pdo->prepare('UPDATE leads SET rd_attempts = rd_attempts + 1, last_rd_error=? WHERE id=?');
    $stmt->execute([($rd['error'] ?? 'unknown_error') . ' status=' . ($rd['status'] ?? 0), $lead_id]);
}

json_response([
    'id' => $lead_id,
    'rd_uuid' => $rd['uuid'] ?? null,
    'rd_status' => $rd['status'] ?? null,
    'queued' => !($rd['ok'] ?? false),
]);