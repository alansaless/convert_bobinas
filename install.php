<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Proteção simples: exige X-Api-Key igual ao definido no .env
$incomingKey = request_header('X-Api-Key');
$expectedKey = env('API_KEY');
if (!$expectedKey || !$incomingKey || !hash_equals($expectedKey, $incomingKey)) {
    json_response(['error' => 'unauthorized'], 401);
}

// Tenta conectar usando credenciais do .env
try {
    $pdo = db();
} catch (Throwable $e) {
    json_response([
        'error' => 'db_connection_failed',
        'message' => $e->getMessage(),
        'hint' => 'Verifique DB_HOST, DB_NAME, DB_USER, DB_PASS no .env e privilégios do usuário.'
    ], 500);
}

// Lê schema.sql e executa CREATE TABLEs; ignora CREATE DATABASE e USE
$schemaPath = __DIR__ . '/schema.sql';
if (!file_exists($schemaPath)) {
    json_response(['error' => 'schema_not_found', 'path' => $schemaPath], 404);
}

$sql = file_get_contents($schemaPath);

// Remove linhas de CREATE DATABASE/USE para evitar necessidade de privilégios
$lines = preg_split('/\r?\n/', $sql);
$filtered = [];
foreach ($lines as $line) {
    $trim = trim($line);
    if ($trim === '' || str_starts_with($trim, '--')) continue; // comentário ou vazio
    if (preg_match('/^CREATE\s+DATABASE/i', $trim)) continue;
    if (preg_match('/^USE\s+/i', $trim)) continue;
    $filtered[] = $line;
}
$sqlFiltered = implode("\n", $filtered);

// Divide em statements por ponto-e-vírgula
$statements = array_filter(array_map('trim', preg_split('/;\s*\n?/', $sqlFiltered)));

$executed = [];
try {
    foreach ($statements as $stmt) {
        if ($stmt === '') continue;
        $pdo->exec($stmt);
        $executed[] = $stmt;
    }
} catch (Throwable $e) {
    json_response([
        'error' => 'schema_execution_failed',
        'message' => $e->getMessage(),
        'failed_statement' => $stmt ?? null
    ], 500);
}

json_response([
    'ok' => true,
    'tables_ready' => true,
    'executed_count' => count($executed)
]);