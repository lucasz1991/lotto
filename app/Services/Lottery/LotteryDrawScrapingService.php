<?php

namespace App\Services\Lottery;

use App\Models\LotteryDraw;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class LotteryDrawScrapingService
{
    public function scrapeGame(string $game, ?string $url = null): LotteryDraw
    {
        $url = trim((string) ($url ?: $this->configuredUrl($game)));

        if ($url === '') {
            throw new InvalidArgumentException('Keine Scraping-URL fuer diese Spielart hinterlegt.');
        }

        $response = Http::timeout(30)
            ->retry(2, 500)
            ->withHeaders([
                'User-Agent' => 'LottoAdmin/1.0 (+'.config('app.url').')',
                'Accept' => 'text/html,application/xhtml+xml,application/xml,text/plain;q=0.9,*/*;q=0.8',
            ])
            ->get($url);

        $response->throw();

        return $this->storeParsedDraw($game, $url, $response->body());
    }

    public function storeParsedDraw(string $game, string $sourceUrl, string $content): LotteryDraw
    {
        $parsed = $this->parse($game, $content);

        return LotteryDraw::query()->updateOrCreate(
            [
                'game' => $game,
                'draw_date' => $parsed['draw_date'],
            ],
            [
                'draw_identifier' => $parsed['draw_date'],
                'numbers' => $parsed['numbers'],
                'bonus_numbers' => $parsed['bonus_numbers'],
                'source_file' => $sourceUrl,
                'raw_data' => [
                    'source' => 'scrape',
                    'url' => $sourceUrl,
                    'parsed_at' => now()->toIso8601String(),
                    'text_excerpt' => mb_substr($this->plainText($content), 0, 3000),
                ],
            ]
        );
    }

    public function parse(string $game, string $content): array
    {
        $text = $this->plainText($content);
        $date = $this->parseDate($text);

        return match ($game) {
            LotteryDraw::GAME_LOTTO_6AUS49 => [
                'draw_date' => $date->toDateString(),
                'numbers' => $this->parseMainNumbers($text, 6, 49, ['gewinnzahlen', 'lottozahlen', 'zahlen']),
                'bonus_numbers' => [
                    'superzahl' => $this->parseSingleNumber($text, ['superzahl', 'super zahl'], 0, 9),
                ],
            ],
            LotteryDraw::GAME_EUROJACKPOT => [
                'draw_date' => $date->toDateString(),
                'numbers' => $this->parseMainNumbers($text, 5, 50, ['gewinnzahlen', '5 aus 50', 'zahlen']),
                'bonus_numbers' => [
                    'euro_numbers' => $this->parseMainNumbers($text, 2, 12, ['eurozahlen', 'euro zahlen', 'ez']),
                ],
            ],
            default => throw new InvalidArgumentException('Unbekannte Spielart.'),
        };
    }

    protected function configuredUrl(string $game): ?string
    {
        $settings = Setting::getValue('lottery', 'games');
        $settings = is_array($settings) ? $settings : [];

        return $settings[$game]['scraping_url'] ?? null;
    }

    protected function plainText(string $content): string
    {
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $content) ?? $content;
        $content = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $content) ?? $content;
        $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = str_replace("\xC2\xA0", ' ', $content);

        return preg_replace('/\s+/u', ' ', trim($content)) ?? trim($content);
    }

    protected function parseDate(string $text): CarbonImmutable
    {
        if (preg_match('/\b(\d{2}\.\d{2}\.\d{4})\b/', $text, $match) === 1) {
            return CarbonImmutable::createFromFormat('d.m.Y', $match[1])->startOfDay();
        }

        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $text, $match) === 1) {
            return CarbonImmutable::createFromFormat('Y-m-d', $match[1])->startOfDay();
        }

        throw new RuntimeException('Ziehungsdatum konnte nicht erkannt werden.');
    }

    protected function parseMainNumbers(string $text, int $count, int $max, array $labels): array
    {
        foreach ($labels as $label) {
            $pattern = '/'.preg_quote($label, '/').'.{0,80}?((?:\b\d{1,2}\b[\s,;|\/-]*){'.$count.',})/iu';

            if (preg_match($pattern, $text, $match) === 1) {
                $numbers = $this->numbersFromText($match[1], 1, $max);

                if (count($numbers) >= $count) {
                    return array_slice($numbers, 0, $count);
                }
            }
        }

        $numbers = $this->numbersFromText($text, 1, $max);

        if (count($numbers) >= $count) {
            return array_slice($numbers, 0, $count);
        }

        throw new RuntimeException('Gewinnzahlen konnten nicht erkannt werden.');
    }

    protected function parseSingleNumber(string $text, array $labels, int $min, int $max): ?int
    {
        foreach ($labels as $label) {
            $pattern = '/'.preg_quote($label, '/').'.{0,30}?\b(\d{1,2})\b/iu';

            if (preg_match($pattern, $text, $match) === 1) {
                $number = (int) $match[1];

                if ($number >= $min && $number <= $max) {
                    return $number;
                }
            }
        }

        return null;
    }

    protected function numbersFromText(string $text, int $min, int $max): array
    {
        preg_match_all('/\b\d{1,2}\b/', $text, $matches);

        $numbers = [];

        foreach ($matches[0] ?? [] as $match) {
            $number = (int) $match;

            if ($number >= $min && $number <= $max) {
                $numbers[] = $number;
            }
        }

        return $numbers;
    }
}
