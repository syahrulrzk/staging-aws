<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BprPksView extends Model
{
    /**
     * The table associated with the model.
     * This is a view table in BPR database
     */
    protected $table = 'view_pks_aws';

    /**
     * The connection name for the model.
     * Reading from BPR database (read-only)
     */
    protected $connection = 'sysbpr';

    /**
     * Disable timestamps since this is a view
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     * Use 'id' from BPR view for ordering.
     */
    protected $primaryKey = 'id';
    public $keyType = 'int';
    public $incrementing = false;

    /**
     * Column mapping from BPR (spaces) to our clean snake_case
     */
    protected array $bprColumns = [
        'id'             => 'bpr_id',
        'No pks'         => 'no_pks',
        'Nama client'    => 'nama_client',
        'Group bisnis'   => 'group_bisnis',
        'start date PKS' => 'start_date_pks',
        'end date PKS'   => 'end_date_pks',
        'Nama LOB'       => 'nama_lob',
        'Kode LOB'       => 'kode_lob',
        'Jenis Kontrak'  => 'jenis_kontrak',
        'Create at'      => 'create_at',
        'Update at'      => 'update_at',
    ];

    /**
     * Build select clause with column aliases using raw SQL
     */
    protected function buildSelectClause(): string
    {
        $selects = [];
        foreach ($this->bprColumns as $bprCol => $alias) {
            $selects[] = "`{$bprCol}` as `{$alias}`";
        }
        return implode(', ', $selects);
    }

    /**
     * Override newQuery to map BPR column names with spaces
     * to clean snake_case attributes using raw select
     */
    public function newQuery()
    {
        $selectClause = $this->buildSelectClause();
        return parent::newQuery()->select(DB::raw($selectClause));
    }

    /**
     * Override to use 'No pks' for ordering instead of non-existent 'id'
     * chunk() uses newQueryWithoutScopes internally
     */
    public function newQueryWithoutScopes(): Builder
    {
        $selectClause = $this->buildSelectClause();
        return parent::newQueryWithoutScopes()
            ->select(DB::raw($selectClause))
            ->orderByRaw('`id` ASC');
    }
}
