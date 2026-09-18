<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - BPR Sync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .method-get { @apply bg-green-100 text-green-800 border-green-300; }
        .method-post { @apply bg-blue-100 text-blue-800 border-blue-300; }
        .method-put { @apply bg-yellow-100 text-yellow-800 border-yellow-300; }
        .method-delete { @apply bg-red-100 text-red-800 border-red-300; }
        pre { @apply bg-gray-900 text-green-400 p-4 rounded-lg text-sm overflow-x-auto; }
        .endpoint-card { @apply bg-white rounded-lg shadow border border-gray-200 mb-6; }
        .param-required { @apply text-red-500 font-bold; }
        .param-optional { @apply text-gray-400; }
        .status-badge { @apply px-2 py-0.5 text-xs font-bold rounded; }
        .status-200 { @apply bg-green-100 text-green-700; }
        .status-201 { @apply bg-blue-100 text-blue-700; }
        .status-404 { @apply bg-yellow-100 text-yellow-700; }
        .status-500 { @apply bg-red-100 text-red-700; }
        .collapse-toggle::after { content: '▸'; display: inline-block; margin-left: 8px; transition: transform 0.2s; }
        .collapse-toggle.open::after { transform: rotate(90deg); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <span class="bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">v1.0</span>
                <h1 class="text-3xl font-bold text-gray-900">BPR Sync API</h1>
            </div>
            <p class="text-gray-500">REST API untuk integrasi Master PKS — BPR ↔ AWS</p>
            <div class="mt-3 flex items-center gap-2 text-sm text-gray-600">
                <span class="bg-gray-200 px-2 py-1 rounded font-mono">Base URL</span>
                <code class="bg-gray-100 px-2 py-1 rounded">http://{{ request()->getHost() }}:{{ request()->getPort() }}/api</code>
            </div>
        </div>

        <!-- Auth Notice -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
            <div class="flex items-start gap-3">
                <span class="text-blue-500 text-xl">ℹ️</span>
                <div>
                    <h3 class="font-semibold text-blue-800">Authentication</h3>
                    <p class="text-sm text-blue-700 mt-1">Saat ini API belum memerlukan authentication. Untuk production, tambahkan API token / Laravel Sanctum.</p>
                </div>
            </div>
        </div>

        <!-- ========== GET /api/master-pks/stats ========== -->
        <div class="endpoint-card">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <span class="method-get px-3 py-1 rounded text-sm font-bold border">GET</span>
                <code class="text-lg font-mono text-gray-800">/api/master-pks/stats</code>
            </div>
            <div class="px-6 py-4">
                <p class="text-gray-600 mb-4">Ambil statistik dashboard: total records, fetched, synced, per LOB.</p>
                
                <h4 class="font-semibold text-sm text-gray-700 mb-2">Response <span class="status-badge status-200">200</span></h4>
<pre>{
  "success": true,
  "data": {
    "total": 136631,
    "fetched": 5000,
    "not_fetched": 131631,
    "synced": 3200,
    "not_synced": 133431,
    "by_status": {
      "pending": 0,
      "success": 136000,
      "failed": 631
    },
    "by_lob": [
      { "kode_lob": "FIF", "nama_lob": "SOB FIF", "total": 63663 }
    ]
  }
}</pre>
            </div>
        </div>

        <!-- ========== GET /api/master-pks/fetch ========== -->
        <div class="endpoint-card">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <span class="method-get px-3 py-1 rounded text-sm font-bold border">GET</span>
                <code class="text-lg font-mono text-gray-800">/api/master-pks/fetch</code>
            </div>
            <div class="px-6 py-4">
                <p class="text-gray-600 mb-4"><strong>Endpoint utama AWS.</strong> Mengambil data yang belum di-sync. Otomatis flag <code>is_fetched = true</code>.</p>
                
                <h4 class="font-semibold text-sm text-gray-700 mb-2">Query Parameters</h4>
                <table class="w-full text-sm mb-4">
                    <thead><tr class="text-left text-gray-500 border-b"><th class="pb-2">Parameter</th><th class="pb-2">Type</th><th class="pb-2">Required</th><th class="pb-2">Description</th></tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr><td class="py-2 font-mono">include_synced</td><td>boolean</td><td class="param-optional">optional</td><td>Sertakan data yang sudah synced</td></tr>
                        <tr><td class="py-2 font-mono">kode_lob</td><td>string</td><td class="param-optional">optional</td><td>Filter berdasarkan kode LOB</td></tr>
                        <tr><td class="py-2 font-mono">no_pks</td><td>string</td><td class="param-optional">optional</td><td>Filter berdasarkan nomor PKS</td></tr>
                    </tbody>
                </table>

                <h4 class="font-semibold text-sm text-gray-700 mb-2">Response <span class="status-badge status-200">200</span></h4>
<pre>{
  "success": true,
  "data": [
    {
      "id": 1,
      "no_pks": "002/PKS/09/2019",
      "nama_client": "JACCS MITRA PINASTHIKA",
      "group_bisnis": "PT. Prima Hijau Lestari",
      "start_date_pks": "2019-09-01",
      "end_date_pks": "2021-08-31",
      "nama_lob": "LOB SM",
      "kode_lob": "SM",
      "jenis_kontrak": "New",
      "is_fetched": true,
      "is_synced": false,
      "sync_status": "success"
    }
  ],
  "meta": {
    "total": 500,
    "fetched_at": "2026-08-29T12:00:00.000000Z"
  }
}</pre>
            </div>
        </div>

        <!-- ========== POST /api/master-pks/{id}/synced ========== -->
        <div class="endpoint-card">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <span class="method-post px-3 py-1 rounded text-sm font-bold border">POST</span>
                <code class="text-lg font-mono text-gray-800">/api/master-pks/{id}/synced</code>
            </div>
            <div class="px-6 py-4">
                <p class="text-gray-600 mb-4">AWS callback: tandai data sudah di-sync setelah diproses.</p>
                
                <h4 class="font-semibold text-sm text-gray-700 mb-2">Path Parameters</h4>
                <table class="w-full text-sm mb-4">
                    <thead><tr class="text-left text-gray-500 border-b"><th class="pb-2">Parameter</th><th class="pb-2">Type</th><th class="pb-2">Description</th></tr></thead>
                    <tbody>
                        <tr><td class="py-2 font-mono">id</td><td>integer</td><td>ID record master_pks</td></tr>
                    </tbody>
                </table>

                <h4 class="font-semibold text-sm text-gray-700 mb-2">Response <span class="status-badge status-200">200</span></h4>
<pre>{
  "success": true,
  "message": "PKS 002/PKS/09/2019 marked as synced to AWS",
  "data": {
    "id": 1,
    "no_pks": "002/PKS/09/2019",
    "is_synced": true,
    "synced_to_aws_at": "2026-08-29T12:00:00.000000Z"
  }
}</pre>

                <h4 class="font-semibold text-sm text-gray-700 mb-2">Error <span class="status-badge status-404">404</span></h4>
<pre>{
  "message": "No query results for model [App\\Models\\MasterPks] 999"
}</pre>
            </div>
        </div>

        <!-- ========== POST /api/master-pks/batch-synced ========== -->
        <div class="endpoint-card">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <span class="method-post px-3 py-1 rounded text-sm font-bold border">POST</span>
                <code class="text-lg font-mono text-gray-800">/api/master-pks/batch-synced</code>
            </div>
            <div class="px-6 py-4">
                <p class="text-gray-600 mb-4">AWS callback: tandai multiple data sebagai synced sekaligus.</p>
                
                <h4 class="font-semibold text-sm text-gray-700 mb-2">Request Body (JSON)</h4>
<pre>{
  "ids": [1, 2, 3, 4, 5]
}</pre>

                <h4 class="font-semibold text-sm text-gray-700 mb-2">Response <span class="status-badge status-200">200</span></h4>
<pre>{
  "success": true,
  "message": "5 records marked as synced to AWS",
  "data": {
    "updated_count": 5,
    "updated_at": "2026-08-29T12:00:00.000000Z"
  }
}</pre>
            </div>
        </div>

        <!-- ========== POST /api/master-pks/sync ========== -->
        <div class="endpoint-card">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <span class="method-post px-3 py-1 rounded text-sm font-bold border">POST</span>
                <code class="text-lg font-mono text-gray-800">/api/master-pks/sync</code>
            </div>
            <div class="px-6 py-4">
                <p class="text-gray-600 mb-4">Manual trigger sinkronisasi dari BPR ke staging.</p>
                
                <h4 class="font-semibold text-sm text-gray-700 mb-2">Response <span class="status-badge status-200">200</span></h4>
<pre>{
  "success": true,
  "message": "Sync triggered successfully",
  "data": {
    "created": 150,
    "updated": 23,
    "skipped": 136458,
    "failed": 0,
    "total": 136631
  }
}</pre>
            </div>
        </div>

        <!-- ========== GET /api/master-pks ========== -->
        <div class="endpoint-card">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <span class="method-get px-3 py-1 rounded text-sm font-bold border">GET</span>
                <code class="text-lg font-mono text-gray-800">/api/master-pks</code>
            </div>
            <div class="px-6 py-4">
                <p class="text-gray-600 mb-4">List semua data master PKS dengan filter & pagination.</p>
                
                <h4 class="font-semibold text-sm text-gray-700 mb-2">Query Parameters</h4>
                <table class="w-full text-sm mb-4">
                    <thead><tr class="text-left text-gray-500 border-b"><th class="pb-2">Parameter</th><th class="pb-2">Type</th><th class="pb-2">Description</th></tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr><td class="py-2 font-mono">sync_status</td><td>string</td><td>pending / success / failed</td></tr>
                        <tr><td class="py-2 font-mono">kode_lob</td><td>string</td><td>Filter LOB (FIF, SM, MSM1, dll)</td></tr>
                        <tr><td class="py-2 font-mono">is_fetched</td><td>boolean</td><td>0 atau 1</td></tr>
                        <tr><td class="py-2 font-mono">is_synced</td><td>boolean</td><td>0 atau 1</td></tr>
                        <tr><td class="py-2 font-mono">search</td><td>string</td><td>Search no_pks / nama_client / group_bisnis</td></tr>
                        <tr><td class="py-2 font-mono">sort_by</td><td>string</td><td>Kolom sorting (default: id)</td></tr>
                        <tr><td class="py-2 font-mono">sort_dir</td><td>string</td><td>asc / desc (default: desc)</td></tr>
                        <tr><td class="py-2 font-mono">per_page</td><td>integer</td><td>Jumlah per halaman (default: 25)</td></tr>
                    </tbody>
                </table>

                <h4 class="font-semibold text-sm text-gray-700 mb-2">Response <span class="status-badge status-200">200</span></h4>
<pre>{
  "success": true,
  "data": { "current_page": 1, "data": [...], "total": 136631 },
  "meta": { "total": 136631, "per_page": 25, "current_page": 1, "last_page": 5466 }
}</pre>
            </div>
        </div>

        <!-- ========== GET /api/master-pks/{id} ========== -->
        <div class="endpoint-card">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <span class="method-get px-3 py-1 rounded text-sm font-bold border">GET</span>
                <code class="text-lg font-mono text-gray-800">/api/master-pks/{id}</code>
            </div>
            <div class="px-6 py-4">
                <p class="text-gray-600 mb-4">Detail single record termasuk sync logs.</p>
                
                <h4 class="font-semibold text-sm text-gray-700 mb-2">Response <span class="status-badge status-200">200</span></h4>
<pre>{
  "success": true,
  "data": {
    "id": 1,
    "no_pks": "002/PKS/09/2019",
    "nama_client": "JACCS MITRA PINASTHIKA",
    "sync_logs": [
      { "action": "create", "status": "success", "synced_at": "2026-08-29T04:42:25" }
    ]
  }
}</pre>
            </div>
        </div>

        <!-- Flow Diagram -->
        <div class="bg-white rounded-lg shadow p-6 mt-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">🔄 Integration Flow</h2>
            <div class="bg-gray-50 rounded-lg p-6 font-mono text-sm">
<pre class="bg-transparent p-0 text-gray-700">
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│    BPR DB    │ ──────▶ │  Staging DB  │ ──────▶ │   AWS App   │
│  (view_pks)  │  Sync   │  (master_pks)│  Fetch  │             │
└─────────────┘  Every   └──────────────┘         └─────────────┘
                  30m           │                        │
                                │    POST /synced        │
                                ◀────────────────────────┘

1. Cron jalan tiap 30 menit → ambil data dari BPR view
2. Data disimpan di staging dengan flagging:
   • is_fetched  → AWS udah ambil data
   • is_synced   → AWS udah proses data
   • sync_status → pending / success / failed
3. AWS hit GET /fetch → ambil data baru
4. AWS proses → hit POST /synced → tandai selesai
</pre>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-400">
            BPR Sync API Documentation v1.0 — Laravel 13 — {{ now()->format('d M Y') }}
        </div>
    </div>
</body>
</html>
