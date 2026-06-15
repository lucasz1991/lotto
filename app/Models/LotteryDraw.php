<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotteryDraw extends Model
{
    public const GAME_LOTTO_6AUS49 = 'lotto_6aus49';

    public const GAME_EUROJACKPOT = 'eurojackpot';

    protected $fillable = [
        'lottery_import_id',
        'game',
        'draw_date',
        'draw_identifier',
        'numbers',
        'bonus_numbers',
        'stake_cents',
        'prize_classes',
        'source_file',
        'raw_data',
    ];

    protected $casts = [
        'draw_date' => 'date',
        'numbers' => 'array',
        'bonus_numbers' => 'array',
        'prize_classes' => 'array',
        'raw_data' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(LotteryImport::class, 'lottery_import_id');
    }

    public static function gameLabels(): array
    {
        return [
            self::GAME_LOTTO_6AUS49 => 'Lotto 6aus49',
            self::GAME_EUROJACKPOT => 'EuroJackpot',
        ];
    }

    public function gameLabel(): string
    {
        return self::gameLabels()[$this->game] ?? $this->game;
    }
}
