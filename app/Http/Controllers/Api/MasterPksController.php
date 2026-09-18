<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterPks;
use App\Services\SyncMasterPksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterPksController extends Controller
{
    /**
     * GET /api/master-pks
     * List all master PKS with filtering & pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = MasterPks::query();

        // Filter by sync status
        if ($request->has('sync_status')) {
            $query->status($request->sync_status);
        }

        // Filter by LOB
        if ($request->has('kode_lob')) {
            $query->lob($request->kode_lob);
        }

        // Filter by fetched status
        if ($request->has('is_fetched')) {
            $query->where('is_fetched', $request->boolean('is_fetched'));
        }

        // Filter by synced to AWS status
        if ($request->has('is_synced')) {
            $query->where('is_synced', $request->boolean('is_synced'));
        }

        // Search by no_pks or nama_client
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_pks', 'like', "%{$search}%")
                  ->orWhere('nama_client', 'like', "%{$search}%")
                  ->orWhere('group_bisnis', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $request->get('per_page', 25);
        $data = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total'         => $data->total(),
                'per_page'      => $data->perPage(),
                'current_page'  => $data->currentPage(),
                'last_page'     => $data->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/master-pks/{id}
     * Get single master PKS detail
     */
    public function show(int $id): JsonResponse
    {
        $masterPks = MasterPks::with('syncLogs')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $masterPks,
        ]);
    }

    /**
     * GET /api/master-pks/fetch
     * AWS endpoint: ambil data yang sudah di-fetch dan belum di-sync
     * This marks data as "fetched" by AWS
     */
    public function fetch(Request $request): JsonResponse
    {
        $query = MasterPks::where('sync_status', 'success');

        // Filter: only data that hasn't been synced to AWS yet
        if (!$request->has('include_synced')) {
            $query->where('is_synced', false);
        }

        // Optional: filter by LOB
        if ($request->has('kode_lob')) {
            $query->lob($request->kode_lob);
        }

        // Optional: filter by PKS number
        if ($request->has('no_pks')) {
            $query->where('no_pks', $request->no_pks);
        }

        $data = $query->get();

        // Mark data as fetched
        $data->each(function ($record) {
            $record->update([
                'is_fetched'   => true,
                'fetched_at'   => $record->fetched_at ?? now(),
                'fetch_count'  => $record->fetch_count + 1,
            ]);
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total'  => $data->count(),
                'fetched_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * POST /api/master-pks/{id}/synced
     * AWS callback: mark data as synced after AWS processes it
     */
    public function markSynced(int $id): JsonResponse
    {
        $masterPks = MasterPks::findOrFail($id);

        $masterPks->update([
            'is_synced'        => true,
            'synced_to_aws_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "PKS {$masterPks->no_pks} marked as synced to AWS",
            'data'    => $masterPks->fresh(),
        ]);
    }

    /**
     * POST /api/master-pks/batch-synced
     * AWS callback: mark multiple records as synced
     */
    public function batchMarkSynced(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:master_pks,id',
        ]);

        $ids = $request->ids;
        $updated = 0;

        foreach ($ids as $id) {
            MasterPks::where('id', $id)->update([
                'is_synced'        => true,
                'synced_to_aws_at' => now(),
            ]);
            $updated++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$updated} records marked as synced to AWS",
            'data'    => [
                'updated_count' => $updated,
                'updated_at'    => now()->toISOString(),
            ],
        ]);
    }

    /**
     * GET /api/master-pks/stats
     * Dashboard statistics
     */
    public function stats(): JsonResponse
    {
        $total      = MasterPks::count();
        $fetched    = MasterPks::fetched()->count();
        $notFetched = MasterPks::notFetched()->count();
        $synced     = MasterPks::synced()->count();
        $notSynced  = MasterPks::notSynced()->count();
        $pending    = MasterPks::status('pending')->count();
        $success    = MasterPks::status('success')->count();
        $failed     = MasterPks::status('failed')->count();

        // Stats per LOB
        $perLob = MasterPks::select('kode_lob', 'nama_lob')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(is_fetched) as fetched')
            ->selectRaw('SUM(is_synced) as synced')
            ->groupBy('kode_lob', 'nama_lob')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'total'      => $total,
                'fetched'    => $fetched,
                'not_fetched'=> $notFetched,
                'synced'     => $synced,
                'not_synced' => $notSynced,
                'by_status'  => [
                    'pending' => $pending,
                    'success' => $success,
                    'failed'  => $failed,
                ],
                'by_lob'     => $perLob,
            ],
        ]);
    }

    /**
     * POST /api/master-pks/sync
     * Manual trigger sync from BPR (admin only)
     */
    public function triggerSync(SyncMasterPksService $syncService): JsonResponse
    {
        $stats = $syncService->sync();

        return response()->json([
            'success' => true,
            'message' => 'Sync triggered successfully',
            'data'    => $stats,
        ]);
    }
}
