<?php

namespace App\Services\Lottery;

use App\Models\LotteryDraw;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class LotteryDrawScrapingService
{
    public const DEFAULT_URLS = [
        LotteryDraw::GAME_LOTTO_6AUS49 => 'https://www.lotto.de/lotto-6aus49/lottozahlen',
        LotteryDraw::GAME_EUROJACKPOT => 'https://www.lotto.de/eurojackpot/zahlen',
    ];

    public function scrapeGame(string $game, ?string $url = null): LotteryDraw
    {
        $url = trim((string) ($url ?: $this->configuredUrl($game)));

        if ($url === '') {
            throw new InvalidArgumentException('Keine Scraping-URL fuer diese Spielart hinterlegt.');
        }

        if ($this->isLottoDePageUrl($url)) {
            return $this->scrapeLottoDeGame($game, $url);
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

    protected function scrapeLottoDeGame(string $game, string $sourceUrl): LotteryDraw
    {
        return match ($game) {
            LotteryDraw::GAME_LOTTO_6AUS49 => $this->scrapeLottoDeLotto6Aus49($sourceUrl),
            LotteryDraw::GAME_EUROJACKPOT => $this->scrapeLottoDeEuroJackpot($sourceUrl),
            default => throw new InvalidArgumentException('Unbekannte Spielart.'),
        };
    }

    protected function scrapeLottoDeLotto6Aus49(string $sourceUrl): LotteryDraw
    {
        $apiUrls = [
            $this->lottoDeApiUrl($sourceUrl, '/api/stats/entities.lotto/last/game/1'),
            $this->lottoDeApiUrl($sourceUrl, '/api/stats/entities.lotto/last/game/2'),
        ];

        $payloads = array_map(fn (string $url) => $this->fetchJson($url), $apiUrls);

        usort($payloads, fn (array $left, array $right) => ($right['drawDate'] ?? 0) <=> ($left['drawDate'] ?? 0));

        return $this->storeParsedData(
            LotteryDraw::GAME_LOTTO_6AUS49,
            $sourceUrl,
            $this->parseLottoDeLotto6Aus49Payload($payloads[0] ?? []),
            [
                'source' => 'lotto.de-api',
                'api_urls' => $apiUrls,
                'payload' => $payloads[0] ?? null,
            ],
        );
    }

    protected function scrapeLottoDeEuroJackpot(string $sourceUrl): LotteryDraw
    {
        $apiUrl = $this->lottoDeApiUrl($sourceUrl, '/api/stats/entities.eurojackpot/last');
        $payload = $this->fetchJson($apiUrl);

        return $this->storeParsedData(
            LotteryDraw::GAME_EUROJACKPOT,
            $sourceUrl,
            $this->parseLottoDeEuroJackpotPayload($payload),
            [
                'source' => 'lotto.de-api',
                'api_urls' => [$apiUrl],
                'payload' => $payload,
            ],
        );
    }

    public function storeParsedDraw(string $game, string $sourceUrl, string $content): LotteryDraw
    {
        $parsed = $this->parse($game, $content);

        return $this->storeParsedData($game, $sourceUrl, $parsed, [
            'source' => 'scrape',
            'url' => $sourceUrl,
            'text_excerpt' => mb_substr($this->plainText($content), 0, 3000),
        ]);
    }

    protected function storeParsedData(string $game, string $sourceUrl, array $parsed, array $rawData): LotteryDraw
    {
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
                'raw_data' => array_merge([
                    'url' => $sourceUrl,
                    'parsed_at' => now()->toIso8601String(),
                ], $rawData),
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

        return $settings[$game]['scraping_url'] ?? self::DEFAULT_URLS[$game] ?? null;
    }

    protected function isLottoDePageUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && str_ends_with(strtolower($host), 'lotto.de');
    }

    protected function lottoDeApiUrl(string $sourceUrl, string $path): string
    {
        $scheme = parse_url($sourceUrl, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($sourceUrl, PHP_URL_HOST) ?: 'www.lotto.de';

        return $scheme.'://'.$host.$path;
    }

    protected function fetchJson(string $url): array
    {
        $response = Http::timeout(30)
            ->retry(2, 500)
            ->withHeaders([
                'User-Agent' => 'LottoAdmin/1.0 (+'.config('app.url').')',
                'Accept' => 'application/json,text/plain;q=0.9,*/*;q=0.8',
            ])
            ->get($url);

        $response->throw();

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Lotto.de API lieferte keine gueltige JSON-Antwort.');
        }

        return $json;
    }

    protected function parseLottoDeLotto6Aus49Payload(array $payload): array
    {
        $numbers = collect($payload['drawNumbersCollection'] ?? [])
            ->sortBy('index')
            ->pluck('drawNumber')
            ->map(fn ($number) => (int) $number)
            ->values()
            ->all();

        if (count($numbers) !== 6) {
            throw new RuntimeException('Lotto.de Gewinnzahlen fuer Lotto 6aus49 konnten nicht erkannt werden.');
        }

        return [
            'draw_date' => $this->dateFromLottoDeTimestamp($payload['drawDate'] ?? null)->toDateString(),
            'numbers' => $numbers,
            'bonus_numbers' => [
                'superzahl' => array_key_exists('superNumber', $payload) ? (int) $payload['superNumber'] : null,
            ],
        ];
    }

    protected function parseLottoDeEuroJackpotPayload(array $payload): array
    {
        $numbers = collect($payload['drawNumbersCollection'] ?? [])
            ->where('drawNumberType', 0)
            ->sortBy('index')
            ->pluck('drawNumber')
            ->map(fn ($number) => (int) $number)
            ->values()
            ->all();

        $euroNumbers = collect($payload['drawNumbersCollection'] ?? [])
            ->where('drawNumberType', 1)
            ->sortBy('index')
            ->pluck('drawNumber')
            ->map(fn ($number) => (int) $number)
            ->values()
            ->all();

        if (count($numbers) !== 5 || count($euroNumbers) !== 2) {
            throw new RuntimeException('Lotto.de Gewinnzahlen fuer EuroJackpot konnten nicht erkannt werden.');
        }

        return [
            'draw_date' => $this->dateFromLottoDeTimestamp($payload['drawDate'] ?? null)->toDateString(),
            'numbers' => $numbers,
            'bonus_numbers' => [
                'euro_numbers' => $euroNumbers,
            ],
        ];
    }

    protected function dateFromLottoDeTimestamp(mixed $timestamp): CarbonInterface
    {
        if (! is_numeric($timestamp)) {
            throw new RuntimeException('Lotto.de Ziehungsdatum konnte nicht erkannt werden.');
        }

        return CarbonImmutable::createFromTimestampMs((int) $timestamp)->setTimezone(config('app.timezone'))->startOfDay();
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
