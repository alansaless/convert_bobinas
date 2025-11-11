<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/rd_client.php';

$pdo = db();
$path = $_GET['path'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function paginate(int $page, int $limit): array {
    $page = max(1, $page);
    $limit = max(1, min(100, $limit));
    $offset = ($page - 1) * $limit;
    return [$limit, $offset];
}

if ($path === 'leads' && $method === 'GET') {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $filter_city = sanitize_str($_GET['city'] ?? null);
    $filter_source = sanitize_str($_GET['source'] ?? null);
    $filter_verified = $_GET['verified'] ?? null;
    $filter_has_whatsapp = $_GET['has_whatsapp'] ?? null;
    $q = sanitize_str($_GET['q'] ?? null);

    $where = [];
    $params = [];
    if ($filter_city) { $where[] = 'city = ?'; $params[] = $filter_city; }
    if ($filter_source) { $where[] = 'source = ?'; $params[] = $filter_source; }
    if ($filter_verified !== null) { $where[] = 'verified = ?'; $params[] = (int)!!$filter_verified; }
    if ($filter_has_whatsapp !== null) { $where[] = '(whatsapp IS ' . ($filter_has_whatsapp ? 'NOT ' : '') . 'NULL AND whatsapp != "")'; }
    if ($q) { $where[] = '(name LIKE ? OR phone LIKE ? OR whatsapp LIKE ? OR website LIKE ? OR address LIKE ? OR city LIKE ? OR category LIKE ?)'; $params = array_merge($params, array_fill(0,7,"%$q%")); }

    $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    [$limitVal, $offsetVal] = paginate($page, $limit);

    $stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM leads $sqlWhere ORDER BY created_at DESC LIMIT $limitVal OFFSET $offsetVal");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
    json_response(['data' => $rows, 'page' => $page, 'limit' => $limitVal, 'total' => $total]);
}

if (preg_match('#^lead/(\d+)$#', $path, $m) && $method === 'GET') {
    $id = (int)$m[1];
    $stmt = $pdo->prepare('SELECT * FROM leads WHERE id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) json_response(['error' => 'not_found'], 404);
    json_response($row);
}

if (preg_match('#^lead/(\d+)/verify$#', $path, $m) && $method === 'POST') {
    $id = (int)$m[1];
    $stmt = $pdo->prepare('UPDATE leads SET verified=1 WHERE id=?');
    $stmt->execute([$id]);
    json_response(['ok' => true]);
}

if (preg_match('#^lead/(\d+)/update$#', $path, $m) && $method === 'POST') {
    $id = (int)$m[1];
    $payload = get_json_input();
    $fields = ['name','email','phone','whatsapp','address','city','state','country','lat','lng','website','rating','reviews_count','category','source'];
    $set = [];
    $params = [];
    foreach ($fields as $f) {
        if (array_key_exists($f, $payload)) { $set[] = "$f = ?"; $params[] = $payload[$f]; }
    }
    if (!$set) json_response(['error' => 'no_changes'], 400);
    $sql = 'UPDATE leads SET ' . implode(', ', $set) . ', updated_at=NOW() WHERE id=?';
    $params[] = $id;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_response(['ok' => true]);
}

if ($path === 'lead/send' && $method === 'POST') {
    $payload = get_json_input();
    $ids = $payload['ids'] ?? [];
    $sent = [];
    foreach ($ids as $id) {
        $stmt = $pdo->prepare('SELECT * FROM leads WHERE id=?');
        $stmt->execute([(int)$id]);
        $lead = $stmt->fetch();
        if (!$lead) continue;
        $res = rd_upsert_contact($pdo, $lead);
        if (($res['ok'] ?? false) && ($res['uuid'] ?? null)) {
            $pdo->prepare('UPDATE leads SET rd_uuid=? WHERE id=?')->execute([$res['uuid'], (int)$id]);
        } else {
            $pdo->prepare('UPDATE leads SET rd_attempts = rd_attempts + 1, last_rd_error=? WHERE id=?')->execute([($res['error'] ?? 'unknown'), (int)$id]);
        }
        $sent[] = ['id' => (int)$id, 'status' => $res['status'] ?? 0, 'ok' => $res['ok'] ?? false];
    }
    json_response(['results' => $sent]);
}

if ($path === 'stats' && $method === 'GET') {
    $withWhatsapp = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE whatsapp IS NOT NULL AND whatsapp != ''")->fetchColumn();
    $withoutWhatsapp = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE (whatsapp IS NULL OR whatsapp = '')")->fetchColumn();
    $avgRating = (float)$pdo->query("SELECT AVG(rating) FROM leads WHERE rating IS NOT NULL")->fetchColumn();
    $verified = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE verified = 1")->fetchColumn();
    json_response(['with_whatsapp' => $withWhatsapp, 'without_whatsapp' => $withoutWhatsapp, 'avg_rating' => round($avgRating, 2), 'verified' => $verified]);
}

if ($path === 'export' && $method === 'GET') {
    // reutiliza filtros
    $filter_city = sanitize_str($_GET['city'] ?? null);
    $filter_source = sanitize_str($_GET['source'] ?? null);
    $filter_verified = $_GET['verified'] ?? null;
    $q = sanitize_str($_GET['q'] ?? null);
    $where = [];
    $params = [];
    if ($filter_city) { $where[] = 'city = ?'; $params[] = $filter_city; }
    if ($filter_source) { $where[] = 'source = ?'; $params[] = $filter_source; }
    if ($filter_verified !== null) { $where[] = 'verified = ?'; $params[] = (int)!!$filter_verified; }
    if ($q) { $where[] = '(name LIKE ? OR phone LIKE ? OR whatsapp LIKE ? OR website LIKE ? OR address LIKE ? OR city LIKE ? OR category LIKE ?)'; $params = array_merge($params, array_fill(0,7,"%$q%")); }
    $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $stmt = $pdo->prepare("SELECT * FROM leads $sqlWhere ORDER BY created_at DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','name','email','phone','whatsapp','address','city','state','country','lat','lng','website','rating','reviews_count','category','source','rd_uuid','verified','created_at','updated_at']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['id'],$r['name'],$r['email'],$r['phone'],$r['whatsapp'],$r['address'],$r['city'],$r['state'],$r['country'],$r['lat'],$r['lng'],$r['website'],$r['rating'],$r['reviews_count'],$r['category'],$r['source'],$r['rd_uuid'],$r['verified'],$r['created_at'],$r['updated_at']]);
    }
    fclose($out);
    exit;
}

if ($path === 'config/save' && $method === 'POST') {
    $payload = get_json_input();
    $allowed = ['RD_CLIENT_ID','RD_CLIENT_SECRET','RD_REDIRECT_URI','RD_REFRESH_TOKEN','API_KEY'];
    $envPath = __DIR__ . '/.env';
    $current = file_exists($envPath) ? file($envPath, FILE_IGNORE_NEW_LINES) : [];
    $map = [];
    foreach ($current as $line) {
        if (str_starts_with(trim($line), '#') || trim($line) === '') continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) $map[$parts[0]] = $parts[1];
    }
    foreach ($allowed as $k) {
        if (isset($payload[$k])) { $map[$k] = $payload[$k]; }
    }
    $new = "";
    foreach ($map as $k => $v) { $new .= $k . '=' . $v . "\n"; }
    @file_put_contents($envPath, $new);
    json_response(['ok' => true]);
}

json_response(['error' => 'not_found'], 404);