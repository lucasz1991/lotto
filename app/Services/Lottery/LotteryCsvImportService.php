<?php

namespace App\Services\Lottery;

use App\Models\LotteryDraw;
use App\Models\LotteryImport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use SplFileObject;
use Throwable;

class LotteryCsvImportService
{
    public function import(string $path, string $originalFilename, ?string $storedPath = null, string $disk = 'private'): LotteryImport
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('CSV-Datei kann nicht gelesen werden.');
        }

        $import = LotteryImport::query()->create([
            'original_filename' => $originalFilename,
            'stored_path' => $storedPath,
            'disk' => $disk,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            return DB::transaction(function () use ($path, $originalFilename, $import): LotteryImport {
                $file = new SplFileObject($path);
                $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
                $file->setCsvControl(';');

                $header = $this->normalizeRow($file->fgetcsv() ?: []);
                $game = $this->detectGame($header, $originalFilename);

                $total = 0;
                $inserted = 0;
                $updated = 0;
                $skipped = 0;

                while (! $file->eof()) {
                    $row = $this->normalizeRow($file->fgetcsv() ?: []);

                    if ($this->isEmptyRow($row)) {
                        continue;
                    }

                    if (! $this->looksLikeDrawDate($row[0] ?? null)) {
                        continue;
                    }

                    $total++;

                    try {
                        $payload = $this->parseDrawRow($game, $row, $originalFilename, $import->id);
                    } catch (Throwable) {
                        $skipped++;

                        continue;
                    }

                    $exists = LotteryDraw::query()
                        ->where('game', $payload['game'])
                        ->whereDate('draw_date', $payload['draw_date'])
                        ->exists();

                    LotteryDraw::query()->updateOrCreate(
                        Arr::only($payload, ['game', 'draw_date']),
                        Arr::except($payload, ['game', 'draw_date'])
                    );

                    $exists ? $updated++ : $inserted++;
                }

                $import->forceFill([
                    'game' => $game,
                    'rows_total' => $total,
                    'rows_imported' => $inserted,
                    'rows_updated' => $updated,
                    'rows_skipped' => $skipped,
                    'status' => 'completed',
                    'message' => null,
                    'finished_at' => now(),
                ])->save();

                return $import->refresh();
            });
        } catch (Throwable $exception) {
            $import->forceFill([
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'finished_at' => now(),
            ])->save();

            throw $exception;
        }
    }

    protected function detectGame(array $header, string $filename): string
    {
        $haystack = mb_strtolower(implode(' ', $header).' '.$filename);

        if (str_contains($haystack, '5 aus 50') || preg_match('/\bej\b/i', $filename)) {
            return LotteryDraw::GAME_EUROJACKPOT;
        }

        if (str_contains($haystack, 'gewinnzahlen') || str_contains($haystack, 'spiel77') || str_contains($haystack, 'super6') || str_contains($haystack, 'lotto')) {
            return LotteryDraw::GAME_LOTTO_6AUS49;
        }

        throw new RuntimeException('Spielart konnte nicht automatisch erkannt werden.');
    }

    protected function parseDrawRow(string $game, array $row, string $sourceFile, int $importId): array
    {
        return match ($game) {
            LotteryDraw::GAME_LOTTO_6AUS49 => $this->parseLotto6aus49Row($row, $sourceFile, $importId),
            LotteryDraw::GAME_EUROJACKPOT => $this->parseEuroJackpotRow($row, $sourceFile, $importId),
            default => throw new InvalidArgumentException('Unbekannte Spielart.'),
        };
    }

    protected function parseLotto6aus49Row(array $row, string $sourceFile, int $importId): array
    {
        $date = $this->parseDate($row[0] ?? null);
        $numbers = $this->parseIntegerSlice($row, 2, 6);

        if (count($numbers) !== 6) {
            throw new InvalidArgumentException('Lotto-Zeile ohne sechs Gewinnzahlen.');
        }

        return [
            'lottery_import_id' => $importId,
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'draw_date' => $date->toDateString(),
            'draw_identifier' => $date->format('Y-m-d'),
            'numbers' => $numbers,
            'bonus_numbers' => [
                'zusatzzahl' => $this->parseIntOrNull($row[8] ?? null),
                's' => $this->parseIntOrNull($row[9] ?? null),
                'superzahl' => $this->parseIntOrNull($row[10] ?? null),
                'spiel77' => $this->cleanNullable($row[11] ?? null),
                'super6' => $this->cleanNullable($row[12] ?? null),
            ],
            'stake_cents' => $this->parseMoneyToCents($row[13] ?? null),
            'prize_classes' => $this->parsePrizeClasses($row, 14, 9),
            'source_file' => $sourceFile,
            'raw_data' => $row,
        ];
    }

    protected function parseEuroJackpotRow(array $row, string $sourceFile, int $importId): array
    {
        $date = $this->parseDate($row[0] ?? null);
        $numbers = $this->parseIntegerSlice($row, 1, 5);
        $euroNumbers = $this->parseIntegerSlice($row, 6, 2);

        if (count($numbers) !== 5 || count($euroNumbers) !== 2) {
            throw new InvalidArgumentException('EuroJackpot-Zeile ohne vollstaendige Gewinnzahlen.');
        }

        return [
            'lottery_import_id' => $importId,
            'game' => LotteryDraw::GAME_EUROJACKPOT,
            'draw_date' => $date->toDateString(),
            'draw_identifier' => $date->format('Y-m-d'),
            'numbers' => $numbers,
            'bonus_numbers' => [
                'euro_numbers' => $euroNumbers,
            ],
            'stake_cents' => $this->parseMoneyToCents($row[8] ?? null),
            'prize_classes' => $this->parsePrizeClasses($row, 9, 12),
            'source_file' => $sourceFile,
            'raw_data' => $row,
        ];
    }

    protected function parsePrizeClasses(array $row, int $startIndex, int $classes): array
    {
        $result = [];

        for ($class = 1; $class <= $classes; $class++) {
            $winnerIndex = $startIndex + (($class - 1) * 2);
            $quoteIndex = $winnerIndex + 1;
            $winner = $this->clean($row[$winnerIndex] ?? null);
            $quote = $this->clean($row[$quoteIndex] ?? null);

            $result[] = [
                'class' => $class,
                'winners' => $this->parseIntOrNull($winner),
                'quote_cents' => $this->parseMoneyToCents($quote),
                'jackpot' => mb_strtolower($winner) === 'jackpot' || mb_strtolower($quote) === 'jackpot',
            ];
        }

        return $result;
    }

    protected function parseIntegerSlice(array $row, int $start, int $length): array
    {
        $values = [];

        for ($index = $start; $index < $start + $length; $index++) {
            $number = $this->parseIntOrNull($row[$index] ?? null);

            if ($number !== null) {
                $values[] = $number;
            }
        }

        return $values;
    }

    protected function parseDate(mixed $value): CarbonImmutable
    {
        $value = $this->clean($value);

        return CarbonImmutable::createFromFormat('d.m.Y', $value)->startOfDay();
    }

    protected function looksLikeDrawDate(mixed $value): bool
    {
        return preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $this->clean($value)) === 1;
    }

    protected function parseIntOrNull(mixed $value): ?int
    {
        $value = $this->clean($value);

        if ($value === '' || $value === '--') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits === '' ? null : (int) $digits;
    }

    protected function parseMoneyToCents(mixed $value): ?int
    {
        $value = $this->clean($value);

        if ($value === '' || $value === '--' || mb_strtolower($value) === 'jackpot') {
            return null;
        }

        $normalized = str_replace(['.', ' '], '', $value);
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            return null;
        }

        return (int) round(((float) $normalized) * 100);
    }

    protected function normalizeRow(array $row): array
    {
        return array_map(fn (mixed $value): string => $this->clean($value), $row);
    }

    protected function cleanNullable(mixed $value): ?string
    {
        $value = $this->clean($value);

        return $value === '' || $value === '--' ? null : $value;
    }

    protected function clean(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && ! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        $value = str_replace("\xC2\xA0", ' ', $value);

        return trim($value);
    }

    protected function isEmptyRow(array $row): bool
    {
        return implode('', array_map(fn (mixed $value): string => $this->clean($value), $row)) === '';
    }
}
