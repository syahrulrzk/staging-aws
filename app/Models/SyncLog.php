<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'sync_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'master_pks_id',
        'action',
        'status',
        'old_data',
        'new_data',
        'error_message',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the master PKS that owns this log
     */
    public function masterPks(): BelongsTo
    {
        return $this->belongsTo(MasterPks::class, 'master_pks_id');
    }
}
