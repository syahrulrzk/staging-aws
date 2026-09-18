<?php

namespace App\Console\Commands;

use App\Services\SyncMasterPksService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('master-pks:sync {--retry : Retry failed records only} {--first-time : Bulk sync for first-time (no logging per record)}')]
#[Description('Sync master PKS data from BPR view to staging database')]
class SyncMasterPks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'master-pks:sync {--retry : Retry failed records only} {--first-time : Bulk sync for first-time (no logging per record)}';

    /**
     * The console command description.
     */
    protected $description = 'Sync master PKS data from BPR view_pks_aws to staging database';

    /**
     * Execute the console command.
     */
    public function handle(SyncMasterPksService $syncService): int
    {
        $this->info('🚀 Starting Master PKS sync...');
        $this->newLine();

        $startTime = microtime(true);

        if ($this->option('retry')) {
            $this->info('📋 Mode: Retry failed records only');
            $this->newLine();
            $stats = $syncService->retryFailed();
            $this->info("✅ Retry completed: {$stats['retried']} records retried");

        } elseif ($this->option('first-time')) {
            $this->info('📋 Mode: First-time bulk sync (fast mode, no per-record logging)');
            $this->newLine();
            $stats = $syncService->syncFirstTime();

            $this->printStats($stats, $startTime);

        } else {
            $this->info('📋 Mode: Incremental sync from BPR');
            $this->newLine();
            $stats = $syncService->sync();

            $this->printStats($stats, $startTime);
        }

        $this->newLine();
        $this->info('✅ Sync completed!');

        return self::SUCCESS;
    }

    /**
     * Print sync statistics table
     */
    protected function printStats(array $stats, float $startTime): void
    {
        $duration = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info('📊 Sync Statistics:');
        $this->newLine();

        $table = [
            ['Metric', 'Count'],
            ['Total records from BPR', number_format($stats['total'])],
            ['New records created', number_format($stats['created'])],
            ['Records updated', number_format($stats['updated'])],
            ['Records skipped (no change)', number_format($stats['skipped'])],
            ['Failed records', number_format($stats['failed'])],
            ['Duration', "{$duration}s"],
        ];

        $this->table(
            array_keys($table[0]),
            array_slice($table, 1)
        );

        if ($stats['failed'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$stats['failed']} records failed. Run with --retry to retry them.");
        }
    }
}
