<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BPR Sync - Master PKS Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .badge { @apply px-2 py-1 text-xs font-medium rounded-full; }
        .badge-success { @apply bg-green-100 text-green-800; }
        .badge-danger { @apply bg-red-100 text-red-800; }
        .badge-warning { @apply bg-yellow-100 text-yellow-800; }
        .badge-secondary { @apply bg-gray-100 text-gray-600; }
        .stat-card { @apply bg-white rounded-xl shadow-sm border border-gray-100 p-5 transition hover:shadow-md; }
        .gradient-blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .gradient-green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .gradient-orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .gradient-purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">🏦 BPR Sync Dashboard</h1>
                <p class="text-gray-500 mt-1">Master PKS — Data Synchronization Status</p>
            </div>
            <a href="{{ route('api-docs') }}" class="bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-600 flex items-center gap-2">
                📖 API Docs
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 font-medium">Total Records</div>
                        <div class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total']) }}</div>
                    </div>
                    <div class="w-12 h-12 rounded-full gradient-blue flex items-center justify-center text-white text-xl">📋</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 font-medium">Fetched dari BPR</div>
                        <div class="text-3xl font-bold text-green-600 mt-1">{{ number_format($stats['fetched']) }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ $stats['total'] > 0 ? round($stats['fetched']/$stats['total']*100, 1) : 0 }}%</div>
                    </div>
                    <div class="w-12 h-12 rounded-full gradient-green flex items-center justify-center text-white text-xl">📥</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 font-medium">Belum di-Fetch</div>
                        <div class="text-3xl font-bold text-yellow-600 mt-1">{{ number_format($stats['not_fetched']) }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ $stats['total'] > 0 ? round($stats['not_fetched']/$stats['total']*100, 1) : 0 }}%</div>
                    </div>
                    <div class="w-12 h-12 rounded-full gradient-orange flex items-center justify-center text-white text-xl">⏳</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 font-medium">Synced ke AWS</div>
                        <div class="text-3xl font-bold text-purple-600 mt-1">{{ number_format($stats['synced']) }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ $stats['total'] > 0 ? round($stats['synced']/$stats['total']*100, 1) : 0 }}%</div>
                    </div>
                    <div class="w-12 h-12 rounded-full gradient-purple flex items-center justify-center text-white text-xl">☁️</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Donut: Sync Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Sync Status</h3>
                <div class="flex justify-center">
                    <canvas id="syncStatusChart" width="220" height="220"></canvas>
                </div>
            </div>
            <!-- Donut: Fetched Status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Fetched Status</h3>
                <div class="flex justify-center">
                    <canvas id="fetchedChart" width="220" height="220"></canvas>
                </div>
            </div>
            <!-- Donut: Synced to AWS -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Synced ke AWS</h3>
                <div class="flex justify-center">
                    <canvas id="syncedChart" width="220" height="220"></canvas>
                </div>
            </div>
        </div>

        <!-- Bar Chart: Per LOB -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-8">
            <h3 class="font-semibold text-gray-800 mb-4">📊 Data per LOB</h3>
            <canvas id="lobChart" height="100"></canvas>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="No PKS / Nama Client..." class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sync Status</label>
                    <select name="sync_status" class="border rounded-lg px-3 py-2 text-sm">
                        <option value="">Semua</option>
                        <option value="pending" {{ request('sync_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="success" {{ request('sync_status') === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('sync_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">LOB</label>
                    <select name="kode_lob" class="border rounded-lg px-3 py-2 text-sm">
                        <option value="">Semua</option>
                        @foreach($lobs as $lob)
                            <option value="{{ $lob->kode_lob }}" {{ request('kode_lob') === $lob->kode_lob ? 'selected' : '' }}>
                                {{ $lob->kode_lob ?? 'N/A' }} - {{ $lob->nama_lob ?? '-' }} ({{ number_format($lob->total) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fetched</label>
                    <select name="is_fetched" class="border rounded-lg px-3 py-2 text-sm">
                        <option value="">Semua</option>
                        <option value="1" {{ request('is_fetched') === '1' ? 'selected' : '' }}>Sudah</option>
                        <option value="0" {{ request('is_fetched') === '0' ? 'selected' : '' }}>Belum</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Synced AWS</label>
                    <select name="is_synced" class="border rounded-lg px-3 py-2 text-sm">
                        <option value="">Semua</option>
                        <option value="1" {{ request('is_synced') === '1' ? 'selected' : '' }}>Sudah</option>
                        <option value="0" {{ request('is_synced') === '0' ? 'selected' : '' }}>Belum</option>
                    </select>
                </div>
                <button type="submit" class="bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-600">Filter</button>
                <a href="{{ route('dashboard') }}" class="bg-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-300">Reset</a>
            </form>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Data Master PKS</h2>
                <span class="text-sm text-gray-500">{{ number_format($data->total()) }} records</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">No PKS</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Client</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Group Bisnis</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">LOB</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Tipe</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Fetched</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Synced AWS</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Last Sync</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($data as $row)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $row->no_pks }}</td>
                                <td class="px-4 py-3 text-gray-700 max-w-[200px] truncate">{{ $row->nama_client }}</td>
                                <td class="px-4 py-3 text-gray-700 max-w-[150px] truncate">{{ $row->group_bisnis }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-gray-700">{{ $row->kode_lob }}</span>
                                    <span class="text-gray-400 text-xs">({{ $row->nama_lob }})</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $row->jenis_kontrak }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($row->is_fetched)
                                        <span class="badge badge-success">✓ {{ $row->fetch_count }}x</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($row->is_synced)
                                        <span class="badge badge-success">✓ Synced</span>
                                    @else
                                        <span class="badge badge-secondary">Belum</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="badge badge-{{ $row->status_badge }}">{{ ucfirst($row->sync_status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ $row->sync_at ? $row->sync_at->format('d M Y H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-gray-400">
                                    <div class="text-4xl mb-2">📭</div>
                                    Tidak ada data ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($data->hasPages())
                <div class="px-5 py-3 border-t border-gray-100">
                    {{ $data->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Charts JS -->
    <script>
        const syncStatus = {
            pending: {{ $stats['pending'] }},
            success: {{ $stats['success'] }},
            failed: {{ $stats['failed'] }}
        };
        const fetched = { fetched: {{ $stats['fetched'] }}, notFetched: {{ $stats['not_fetched'] }} };
        const syncedToAws = { synced: {{ $stats['synced'] }}, notSynced: {{ $stats['not_synced'] }} };

        // Sync Status Donut
        new Chart(document.getElementById('syncStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Success', 'Pending', 'Failed'],
                datasets: [{
                    data: [syncStatus.success, syncStatus.pending, syncStatus.failed],
                    backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } }
                }
            }
        });

        // Fetched Donut
        new Chart(document.getElementById('fetchedChart'), {
            type: 'doughnut',
            data: {
                labels: ['Sudah di-Fetch', 'Belum di-Fetch'],
                datasets: [{
                    data: [fetched.fetched, fetched.notFetched],
                    backgroundColor: ['#22c55e', '#e5e7eb'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } }
                }
            }
        });

        // Synced to AWS Donut
        new Chart(document.getElementById('syncedChart'), {
            type: 'doughnut',
            data: {
                labels: ['Synced ke AWS', 'Belum Synced'],
                datasets: [{
                    data: [syncedToAws.synced, syncedToAws.notSynced],
                    backgroundColor: ['#8b5cf6', '#e5e7eb'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } }
                }
            }
        });

        // LOB Bar Chart
        const lobData = @json($lobs->map(fn($l) => [
            'label' => ($l->kode_lob ?? 'N/A') . ' (' . ($l->nama_lob ?? '-') . ')',
            'count' => (int) $l->total
        ]));

        new Chart(document.getElementById('lobChart'), {
            type: 'bar',
            data: {
                labels: lobData.map(d => d.label),
                datasets: [{
                    label: 'Jumlah Records',
                    data: lobData.map(d => d.count),
                    backgroundColor: [
                        '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
                        '#ec4899', '#f43f5e', '#f97316', '#eab308'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
