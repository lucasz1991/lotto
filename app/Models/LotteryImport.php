<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotteryImport extends Model
{
    protected $fillable = [
        'game',
        'original_filename',
        'stored_path',
        'disk',
        'rows_total',
        'rows_imported',
        'rows_updated',
        'rows_skipped',
        'status',
        'message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function draws(): HasMany
    {
        return $this->hasMany(LotteryDraw::class);
    }
}
