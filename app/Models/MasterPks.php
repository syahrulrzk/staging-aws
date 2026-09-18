<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterPks extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'master_pks';

    /**
     * The connection name for the model.
     * Using default (staging) database
     */
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'bpr_id',
        'no_pks',
        'nama_client',
        'group_bisnis',
        'start_date_pks',
        'end_date_pks',
        'nama_lob',
        'kode_lob',
        'jenis_kontrak',
        'create_at',
        'update_at_bpr',
        'retry_at',
        'sync_at',
        'sync_status',
        // Flagging fields
        'is_fetched',
        'fetched_at',
        'is_synced',
        'synced_to_aws_at',
        'fetch_count',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date_pks' => 'date',
        'end_date_pks' => 'date',
        'create_at' => 'datetime',
        'update_at_bpr' => 'datetime',
        'retry_at' => 'datetime',
        'sync_at' => 'datetime',
        'fetched_at' => 'datetime',
        'synced_to_aws_at' => 'datetime',
        'is_fetched' => 'boolean',
        'is_synced' => 'boolean',
    ];

    /**
     * Get sync logs for this PKS
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'master_pks_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: filter by sync status
     */
    public function scopeStatus($query, ?string $status)
    {
        if (is_null($status)) return $query;
        return $query->where('sync_status', $status);
    }

    /**
     * Scope: filter by LOB
     */
    public function scopeLob($query, ?string $kodeLob)
    {
        if (is_null($kodeLob)) return $query;
        return $query->where('kode_lob', $kodeLob);
    }

    /**
     * Scope: data yang sudah pernah di-fetch dari BPR
     */
    public function scopeFetched($query)
    {
        return $query->where('is_fetched', true);
    }

    /**
     * Scope: data yang belum pernah di-fetch
     */
    public function scopeNotFetched($query)
    {
        return $query->where('is_fetched', false);
    }

    /**
     * Scope: data yang sudah di-sync ke AWS
     */
    public function scopeSynced($query)
    {
        return $query->where('is_synced', true);
    }

    /**
     * Scope: data yang belum di-sync ke AWS
     */
    public function scopeNotSynced($query)
    {
        return $query->where('is_synced', false);
    }

    // ==================== ACCESSORS ====================

    /**
     * Status badge color untuk UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->sync_status) {
            'success' => 'success',
            'failed'  => 'danger',
            'pending' => 'warning',
            default   => 'secondary',
        };
    }

    /**
     * Fetched status badge
     */
    public function getFetchedBadgeAttribute(): string
    {
        return $this->is_fetched ? 'success' : 'secondary';
    }

    /**
     * Synced to AWS status badge
     */
    public function getSyncedBadgeAttribute(): string
    {
        return $this->is_synced ? 'success' : 'secondary';
    }
}
