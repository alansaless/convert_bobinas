<?php
// Log em arquivo e persistência em tabela rd_logs

function ensure_storage(): void {
    $dir = __DIR__ . '/../storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

function app_log(string $message): void {
    ensure_storage();
    $file = __DIR__ . '/../storage/logs/app.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    @file_put_contents($file, $line, FILE_APPEND);
}

function log_rd_event(PDO $pdo, ?int $lead_id, string $action, ?int $status_code, ?string $response_body): void {
    $stmt = $pdo->prepare('INSERT INTO rd_logs (lead_id, action, status_code, response_body) VALUES (?, ?, ?, ?)');
    $stmt->execute([$lead_id, $action, $status_code, $response_body]);
}