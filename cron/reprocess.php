<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../rd_client.php';
$pdo = db();
$max = (int)env('RD_MAX_ATTEMPTS', '5');

$stmt = $pdo->prepare('SELECT * FROM leads WHERE rd_uuid IS NULL AND rd_attempts < ? ORDER BY updated_at ASC LIMIT 50');
$stmt->execute([$max]);
$rows = $stmt->fetchAll();

foreach ($rows as $lead) {
    $res = rd_upsert_contact($pdo, $lead);
    if (($res['ok'] ?? false) && ($res['uuid'] ?? null)) {
        $pdo->prepare('UPDATE leads SET rd_uuid=? WHERE id=?')->execute([$res['uuid'], $lead['id']]);
        app_log('Reprocessado com sucesso lead ' . $lead['id']);
    } else {
        $pdo->prepare('UPDATE leads SET rd_attempts = rd_attempts + 1, last_rd_error=? WHERE id=?')->execute([($res['error'] ?? 'unknown'), $lead['id']]);
        app_log('Falha reprocessando lead ' . $lead['id'] . ' status=' . ($res['status'] ?? 0));
    }
}
echo "Done: " . count($rows) . " leads reprocessados\n";