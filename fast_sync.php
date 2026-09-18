<?php
/**
 * Fast sync script - direct PDO, handles NULLs properly
 * Run: php bpr-sync/fast_sync.php
 */

$startTime = microtime(true);

$bprPdo  = new PDO('mysql:host=192.168.0.41;port=3306;dbname=core;charset=utf8mb4', 'appslms', 'PasswordYangKuat2026#', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stgPdo  = new PDO('mysql:host=192.168.0.7;port=3306;dbname=db_staging_aws;charset=utf8mb4', 'bpr_sync', 'PasswordYangKuat2026#', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stgPdo->exec("SET SESSION sql_mode = ''");

$total = (int)$bprPdo->query("SELECT COUNT(*) FROM view_pks_aws")->fetchColumn();
echo "📊 Total BPR: " . number_format($total) . "\n";

$cols = 'bpr_id,no_pks,nama_client,group_bisnis,start_date_pks,end_date_pks,nama_lob,kode_lob,jenis_kontrak,create_at,update_at_bpr,sync_status,is_fetched,is_synced,fetch_count,sync_at,created_at,updated_at';
$singlePlaceholders = '(' . implode(',', array_fill(0, 18, '?')) . ')';
$now = date('Y-m-d H:i:s');

$safeDate = function($v) {
    if (empty($v) || $v === '0000-00-00 00:00:00' || $v === '0000-00-00') return null;
    // Reject negative years or invalid MySQL datetime format
    if (preg_match('/^-|^[0-9]{4}-[0-9]{2}-[0-9]{2}( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', trim((string) $v)) === 0) return null;
    return $v;
};
$safeDateOnly = function($v) {
    if (empty($v) || $v === '0000-00-00') return null;
    if (preg_match('/^-/', trim((string) $v))) return null;
    return $v;
};

// Chunked read from BPR, insert into staging
$offset = 0;
$chunkSize = 1000;
$created = 0;
$failedRows = [];

while ($offset < $total) {
    $stmt = $bprPdo->prepare("
        SELECT 
            `id` as c_id,
            IFNULL(`No pks`,'') as c0,
            `Nama client` as c1,
            `Group bisnis` as c2,
            `start date PKS` as c3,
            `end date PKS` as c4,
            `Nama LOB` as c5,
            `Kode LOB` as c6,
            `Jenis Kontrak` as c7,
            `Create at` as c8,
            `Update at` as c9
        FROM view_pks_aws 
        ORDER BY `id` ASC
        LIMIT :lim OFFSET :off
    ");
    $stmt->bindValue(':lim', $chunkSize, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_NUM);

    if (empty($rows)) break;

    // Build batch
    $values = [];
    $params = [];
    foreach ($rows as $r) {
        $values[] = $singlePlaceholders;
        $params[] = $r['c_id']; // bpr_id (unix timestamp from BPR)
        $params[] = $r[0];  // no_pks
        $params[] = $r[1];  // nama_client
        $params[] = $r[2];  // group_bisnis
        $params[] = $safeDateOnly($r[3]);  // start_date_pks
        $params[] = $safeDateOnly($r[4]);  // end_date_pks
        $params[] = $r[5];  // nama_lob
        $params[] = $r[6];  // kode_lob
        $params[] = $r[7];  // jenis_kontrak
        $params[] = $safeDate($r[8]); // create_at
        $params[] = $safeDate($r[9]); // update_at_bpr
        $params[] = 'success';
        $params[] = 0;
        $params[] = 0;
        $params[] = 0;
        $params[] = $now;
        $params[] = $now;
        $params[] = $now;
    }

    $sql = "INSERT IGNORE INTO master_pks ($cols) VALUES " . implode(',', $values);

    try {
        $stgPdo->prepare($sql)->execute($params);
        $created += count($rows);
    } catch (PDOException $e) {
        // Batch failed - insert one by one, skip failures
        foreach ($rows as $r) {
            try {
                $singleParams = [
                    $r['c_id'], // bpr_id
                    $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7],
                    $safeDate($r[8]), $safeDate($r[9]),
                    'success', 0, 0, 0, $now, $now, $now,
                ];
                $stgPdo->prepare("INSERT IGNORE INTO master_pks ($cols) VALUES $singlePlaceholders")->execute($singleParams);
                $created++;
            } catch (PDOException $e2) {
                $failedRows[] = "{$r[0]}/{$r[6]}: {$e2->getMessage()}";
            }
        }
    }

    $offset += $chunkSize;
    $pct = round(($created / $total) * 100, 1);
    echo "\r📥 " . number_format($created) . "/" . number_format($total) . " ({$pct}%)";
}

$duration = round(microtime(true) - $startTime, 2);

echo "\n\n=== DONE ===\n";
echo "✅ Inserted: " . number_format($created) . "\n";
echo "❌ Failed:   " . count($failedRows) . "\n";
echo "⏱️  Duration: {$duration}s\n";

if (!empty($failedRows)) {
    echo "\nFailed rows:\n";
    foreach (array_slice($failedRows, 0, 10) as $f) echo "  - {$f}\n";
}
