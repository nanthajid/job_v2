<?php
require_once __DIR__ . '/../config/database.php';

function dataImportGetConfig(string $key): ?array
{
    $tables = require __DIR__ . '/data_import_config.php';
    return $tables[$key] ?? null;
}

function dataImportGetAllConfig(): array
{
    return require __DIR__ . '/data_import_config.php';
}

function dataImportGetStats(PDO $pdo, array $cfg): array
{
    $table       = '`' . $cfg['table'] . '`';
    $sourceTable = '`' . IMPORT_SOURCE_DB . '`.`' . $cfg['table'] . '`';
    $pk          = '`' . $cfg['pk'] . '`';

    $totalSource = (int) $pdo->query("SELECT COUNT(*) FROM {$sourceTable}")->fetchColumn();
    $totalTarget = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    $willUpdate  = (int) $pdo->query(
        "SELECT COUNT(*) FROM {$sourceTable} s INNER JOIN {$table} t ON t.{$pk} = s.{$pk}"
    )->fetchColumn();
    $willInsert  = $totalSource - $willUpdate;

    return [
        'total_source' => $totalSource,
        'total_target' => $totalTarget,
        'will_insert'  => $willInsert,
        'will_update'  => $willUpdate,
    ];
}

function dataImportRun(PDO $pdo, array $cfg): array
{
    $stats = dataImportGetStats($pdo, $cfg);

    $table       = '`' . $cfg['table'] . '`';
    $sourceTable = '`' . IMPORT_SOURCE_DB . '`.`' . $cfg['table'] . '`';
    $colList     = implode(', ', array_map(fn($c) => "`$c`", $cfg['columns']));
    $updateList  = implode(', ', array_map(fn($c) => "`$c` = VALUES(`$c`)", $cfg['columns']));

    $pdo->beginTransaction();
    $pdo->exec(
        "INSERT INTO {$table} ({$colList})
         SELECT {$colList} FROM {$sourceTable}
         ON DUPLICATE KEY UPDATE {$updateList}"
    );
    $pdo->commit();

    return $stats;
}
