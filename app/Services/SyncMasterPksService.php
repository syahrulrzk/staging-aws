<?php

namespace App\Services;

use App\Models\BprPksView;
use App\Models\MasterPks;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMasterPksService
{
    protected array $fieldMap = [
        'id'             => 'bpr_id',
        'no_pks'         => 'no_pks',
        'nama_client'    => 'nama_client',
        'group_bisnis'   => 'group_bisnis',
        'start_date_pks' => 'start_date_pks',
        'end_date_pks'   => 'end_date_pks',
        'nama_lob'       => 'nama_lob',
        'kode_lob'       => 'kode_lob',
        'jenis_kontrak'  => 'jenis_kontrak',
        'create_at'      => 'create_at',
        'update_at'      => 'update_at_bpr',
    ];

    protected array $stats = [
        'created'  => 0,
        'updated'  => 0,
        'skipped'  => 0,
        'failed'   => 0,
        'total'    => 0,
    ];

    protected int $chunkSize = 500;

    /**
     * Sanitize datetime - convert invalid dates to null
     * Handles: '0000-00-00', negative years, etc.
     */
    protected function sanitizeDatetime($value): ?string
    {
        if (empty($value) || $value === '0000-00-00 00:00:00' || $value === '0000-00-00') {
            return null;
        }
        // Reject negative years or invalid MySQL datetime format
        if (preg_match('/^-|^[0-9]{4}-[0-9]{2}-[0-9]{2}( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', trim((string) $value)) === 0) {
            return null;
        }
        return (string) $value;
    }

    /**
     * Sanitize date - convert '0000-00-00' to null
     */
    protected function sanitizeDate($value): ?string
    {
        if (empty($value) || $value === '0000-00-00') {
            return null;
        }
        // Reject negative years
        if (preg_match('/^-/', trim((string) $value))) {
            return null;
        }
        return (string) $value;
    }

    /**
     * Run full sync (incremental, with change detection)
     */
    public function sync(): array
    {
        $startTime = now();
        $this->stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'total' => 0];

        Log::info('[SyncMasterPks] Starting incremental sync...');

        try {
            $totalFromBpr = BprPksView::count();
            $this->stats['total'] = $totalFromBpr;

            if ($totalFromBpr === 0) {
                Log::warning('[SyncMasterPks] No data found in BPR view_pks_aws');
                return $this->stats;
            }

            Log::info("[SyncMasterPks] Found {$totalFromBpr} records. Starting chunked sync...");

            BprPksView::query()->chunk($this->chunkSize, function ($chunk) {
                foreach ($chunk as $bprRecord) {
                    $this->processRecord($bprRecord);
                }
                $processed = $this->stats['created'] + $this->stats['updated'] + $this->stats['skipped'] + $this->stats['failed'];
                Log::info("[SyncMasterPks] Progress: {$processed}/{$this->stats['total']}");
            });

            $duration = $startTime->diffInSeconds(now());
            Log::info("[SyncMasterPks] Sync completed in {$duration}s", $this->stats);

        } catch (\Exception $e) {
            Log::error("[SyncMasterPks] Sync failed: {$e->getMessage()}");
            $this->stats['failed']++;
        }

        return $this->stats;
    }

    /**
     * First-time bulk sync using raw DB insert (bypasses model, handles 0000-00-00 dates)
     */
    public function syncFirstTime(): array
    {
        $startTime = now();
        $this->stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'total' => 0];

        Log::info('[SyncMasterPks] Starting FIRST-TIME bulk sync...');

        try {
            $existingCount = MasterPks::count();
            if ($existingCount > 0) {
                Log::info("[SyncMasterPks] Staging already has {$existingCount} records. Using incremental sync.");
                return $this->sync();
            }

            $totalFromBpr = BprPksView::count();
            $this->stats['total'] = $totalFromBpr;

            if ($totalFromBpr === 0) {
                Log::warning('[SyncMasterPks] No data found in BPR view_pks_aws');
                return $this->stats;
            }

            Log::info("[SyncMasterPks] First-time sync: {$totalFromBpr} records. Using raw bulk insert...");

            $columns = [
                'bpr_id', 'no_pks', 'nama_client', 'group_bisnis', 'start_date_pks', 'end_date_pks',
                'nama_lob', 'kode_lob', 'jenis_kontrak', 'create_at', 'update_at_bpr',
                'sync_status', 'is_fetched', 'is_synced', 'fetch_count', 'sync_at', 'created_at', 'updated_at',
            ];

            BprPksView::query()->chunk($this->chunkSize, function ($chunk) use ($columns) {
                $rows = [];
                $now = now()->format('Y-m-d H:i:s');

                foreach ($chunk as $bprRecord) {
                    $rows[] = [
                        $bprRecord->bpr_id,
                        $bprRecord->no_pks ?? '',
                        $bprRecord->nama_client ?? '',
                        $bprRecord->group_bisnis ?? '',
                        $this->sanitizeDate($bprRecord->start_date_pks),
                        $this->sanitizeDate($bprRecord->end_date_pks),
                        $bprRecord->nama_lob ?? '',
                        $bprRecord->kode_lob ?? '',
                        $bprRecord->jenis_kontrak ?? '',
                        $this->sanitizeDatetime($bprRecord->create_at),
                        $this->sanitizeDatetime($bprRecord->update_at),
                        'success',
                        0,
                        0,
                        0,
                        $now,
                        $now,
                        $now,
                    ];
                }

                // Build raw INSERT query - handles NULL dates properly
                $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
                $allPlaceholders = array_fill(0, count($rows), $placeholders);
                $query = "INSERT INTO `master_pks` (`" . implode('`,`', $columns) . "`) VALUES " . implode(',', $allPlaceholders);

                // Flatten values for binding
                $bindings = [];
                foreach ($rows as $row) {
                    foreach ($row as $value) {
                        $bindings[] = $value;
                    }
                }

                DB::connection('mysql')->insert($query, $bindings);
                $this->stats['created'] += count($rows);

                Log::info("[SyncMasterPks] Bulk inserted: {$this->stats['created']}/{$this->stats['total']}");
            });

            $duration = $startTime->diffInSeconds(now());
            Log::info("[SyncMasterPks] First-time sync completed in {$duration}s", $this->stats);

        } catch (\Exception $e) {
            Log::error("[SyncMasterPks] First-time sync failed: {$e->getMessage()}");
            $this->stats['failed']++;
        }

        return $this->stats;
    }

    /**
     * Process single record (incremental sync)
     */
    protected function processRecord(BprPksView $bprRecord): void
    {
        try {
            $mappedData = $this->mapData($bprRecord);

            $existing = MasterPks::where('no_pks', $mappedData['no_pks'])
                ->where('kode_lob', $mappedData['kode_lob'])
                ->first();

            if ($existing) {
                if ($this->hasChanges($existing, $mappedData)) {
                    $oldData = $existing->toArray();
                    $existing->update($mappedData);

                    SyncLog::create([
                        'master_pks_id' => $existing->id,
                        'action'        => 'update',
                        'status'        => 'success',
                        'old_data'      => $oldData,
                        'new_data'      => $existing->fresh()->toArray(),
                        'synced_at'     => now(),
                    ]);

                    $this->stats['updated']++;
                } else {
                    $this->stats['skipped']++;
                }
            } else {
                $newRecord = MasterPks::create(array_merge($mappedData, [
                    'sync_status' => 'success',
                    'sync_at'     => now(),
                ]));

                SyncLog::create([
                    'master_pks_id' => $newRecord->id,
                    'action'        => 'create',
                    'status'        => 'success',
                    'new_data'      => $newRecord->toArray(),
                    'synced_at'     => now(),
                ]);

                $this->stats['created']++;
            }
        } catch (\Exception $e) {
            $this->stats['failed']++;
            Log::error("[SyncMasterPks] Failed: {$e->getMessage()}", [
                'no_pks'   => $bprRecord->no_pks ?? 'N/A',
                'kode_lob' => $bprRecord->kode_lob ?? 'N/A',
            ]);
        }
    }

    protected function mapData(BprPksView $bprRecord): array
    {
        $data = [];
        foreach ($this->fieldMap as $bprField => $stagingField) {
            $value = $bprRecord->{$bprField} ?? null;
            // Sanitize datetime fields
            if (in_array($stagingField, ['create_at', 'update_at_bpr'])) {
                $value = $this->sanitizeDatetime($value);
            }
            $data[$stagingField] = $value;
        }
        return $data;
    }

    protected function hasChanges(MasterPks $existing, array $newData): bool
    {
        foreach ($newData as $key => $value) {
            if (in_array($key, ['create_at', 'update_at_bpr'])) {
                continue;
            }
            $existingValue = $existing->getAttributes()[$key] ?? null;
            if ($existingValue != $value) {
                return true;
            }
        }
        return false;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function retryFailed(): array
    {
        $failedRecords = MasterPks::status('failed')->get();
        Log::info("[SyncMasterPks] Retrying {$failedRecords->count()} failed records");

        foreach ($failedRecords as $record) {
            try {
                $bprData = BprPksView::where('no_pks', $record->no_pks)
                    ->where('kode_lob', $record->kode_lob)
                    ->first();

                if ($bprData) {
                    $mappedData = $this->mapData($bprData);
                    $record->update(array_merge($mappedData, [
                        'sync_status' => 'success',
                        'retry_at'    => now(),
                        'sync_at'     => now(),
                    ]));
                }
            } catch (\Exception $e) {
                Log::error("[SyncMasterPks] Retry failed for {$record->no_pks}: {$e->getMessage()}");
            }
        }

        return ['retried' => $failedRecords->count()];
    }
}
