<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotteryNumberCheck extends Model
{
    protected $fillable = [
        'user_id',
        'game',
        'label',
        'main_numbers',
        'bonus_numbers',
        'score',
        'rating',
        'analysis',
        'last_evaluated_at',
    ];

    protected $casts = [
        'main_numbers' => 'array',
        'bonus_numbers' => 'array',
        'analysis' => 'array',
        'last_evaluated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameLabel(): string
    {
        return LotteryDraw::gameLabels()[$this->game] ?? $this->game;
    }
}
