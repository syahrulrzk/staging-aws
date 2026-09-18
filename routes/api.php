<?php

use App\Http\Controllers\Api\MasterPksController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes for AWS to fetch master PKS data
|
*/

Route::prefix('master-pks')->group(function () {

    // GET /api/master-pks/stats - Dashboard statistics
    Route::get('/stats', [MasterPksController::class, 'stats']);

    // GET /api/master-pks/fetch - AWS fetch data (marked as fetched)
    Route::get('/fetch', [MasterPksController::class, 'fetch']);

    // POST /api/master-pks/{id}/synced - AWS mark as synced
    Route::post('/{id}/synced', [MasterPksController::class, 'markSynced']);

    // POST /api/master-pks/batch-synced - AWS batch mark as synced
    Route::post('/batch-synced', [MasterPksController::class, 'batchMarkSynced']);

    // POST /api/master-pks/sync - Manual trigger sync
    Route::post('/sync', [MasterPksController::class, 'triggerSync']);

    // GET /api/master-pks - List all (admin)
    Route::get('/', [MasterPksController::class, 'index']);

    // GET /api/master-pks/{id} - Detail (admin)
    Route::get('/{id}', [MasterPksController::class, 'show']);
});
