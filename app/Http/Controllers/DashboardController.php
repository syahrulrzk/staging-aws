<?php

namespace App\Http\Controllers;

use App\Models\MasterPks;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show dashboard with statistics and data
     */
    public function index(Request $request)
    {
        $query = MasterPks::query();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_pks', 'like', "%{$search}%")
                  ->orWhere('nama_client', 'like', "%{$search}%")
                  ->orWhere('group_bisnis', 'like', "%{$search}%");
            });
        }

        // Filter by sync status
        if ($request->has('sync_status') && $request->sync_status !== '') {
            $query->status($request->sync_status);
        }

        // Filter by LOB
        if ($request->has('kode_lob') && $request->kode_lob !== '') {
            $query->lob($request->kode_lob);
        }

        // Filter by fetched status
        if ($request->has('is_fetched') && $request->is_fetched !== '') {
            $query->where('is_fetched', $request->boolean('is_fetched'));
        }

        // Filter by synced status
        if ($request->has('is_synced') && $request->is_synced !== '') {
            $query->where('is_synced', $request->boolean('is_synced'));
        }

        // Sort & paginate
        $sortBy = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'desc');
        $data = $query->orderBy($sortBy, $sortDir)->paginate(20)->withQueryString();

        // Statistics
        $stats = [
            'total'      => MasterPks::count(),
            'fetched'    => MasterPks::fetched()->count(),
            'not_fetched'=> MasterPks::notFetched()->count(),
            'synced'     => MasterPks::synced()->count(),
            'not_synced' => MasterPks::notSynced()->count(),
            'pending'    => MasterPks::status('pending')->count(),
            'success'    => MasterPks::status('success')->count(),
            'failed'     => MasterPks::status('failed')->count(),
        ];

        // Get distinct LOBs with counts for chart & dropdown
        $lobs = MasterPks::select('kode_lob', 'nama_lob')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('kode_lob', 'nama_lob')
            ->orderByDesc('total')
            ->get();

        return view('dashboard', compact('data', 'stats', 'lobs'));
    }
}
