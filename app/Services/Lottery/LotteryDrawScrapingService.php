<?php

namespace App\Services\Lottery;

use App\Models\LotteryDraw;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

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
        try {
            $response = Http::timeout(30)
                ->retry(2, 500)
                ->withHeaders([
                    'User-Agent' => 'LottoAdmin/1.0 (+'.config('app.url').')',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml,text/plain;q=0.9,*/*;q=0.8',
                ])
                ->get($sourceUrl);

            $response->throw();

            return $this->storeParsedDraw($game, $sourceUrl, $response->body());
        } catch (Throwable) {
            // lotto.de also exposes JSON endpoints; keep them as a fallback when the page markup is unavailable.
        }

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
        $attributes = [
            'draw_identifier' => $parsed['draw_date'],
            'numbers' => $parsed['numbers'],
            'bonus_numbers' => $parsed['bonus_numbers'],
            'stake_cents' => $parsed['stake_cents'] ?? null,
            'prize_classes' => $parsed['prize_classes'] ?? null,
            'source_file' => $sourceUrl,
            'raw_data' => array_merge([
                'url' => $sourceUrl,
                'parsed_at' => now()->toIso8601String(),
            ], $rawData),
        ];

        $draw = LotteryDraw::query()
            ->where('game', $game)
            ->whereDate('draw_date', $parsed['draw_date'])
            ->first();

        if ($draw) {
            $draw->forceFill($attributes)->save();

            return $draw->refresh();
        }

        return LotteryDraw::query()->create(array_merge([
            'game' => $game,
            'draw_date' => $parsed['draw_date'],
        ], $attributes));
    }

    public function parse(string $game, string $content): array
    {
        $htmlParsed = $this->parseLottoDePageHtml($game, $content);

        if ($htmlParsed !== null) {
            return $htmlParsed;
        }

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

    protected function parseLottoDePageHtml(string $game, string $content): ?array
    {
        if (! str_contains($content, 'OddsDateInput') || ! str_contains($content, 'DrawContainer')) {
            return null;
        }

        $xpath = $this->xpathForHtml($content);
        $drawContainer = $this->firstElement($xpath, '//*[contains(concat(" ", normalize-space(@class), " "), " DrawContainer ")]');

        if (! $drawContainer) {
            return null;
        }

        $dateText = $this->nodeText($this->firstElement($xpath, './/*[contains(concat(" ", normalize-space(@class), " "), " WinningNumbers__date ")]', $drawContainer));
        $drawDate = $this->parseDate($dateText)->toDateString();
        $numberGroups = $this->parseDrawNumberGroups($xpath, $drawContainer);
        $tables = $this->parseOddsTables($xpath, $drawContainer);

        return match ($game) {
            LotteryDraw::GAME_LOTTO_6AUS49 => [
                'draw_date' => $drawDate,
                'numbers' => $this->requireNumberCount($numberGroups['main'] ?? [], 6, 'Lotto 6aus49 Gewinnzahlen'),
                'bonus_numbers' => array_filter([
                    'superzahl' => $numberGroups['superzahl'][0] ?? null,
                    'spiel77' => $numberGroups['spiel77'] ?? null,
                    'super6' => $numberGroups['super6'] ?? null,
                ], fn (mixed $value): bool => $value !== null && $value !== []),
                'stake_cents' => $tables[0]['stake_cents'] ?? null,
                'prize_classes' => $tables[0]['prize_classes'] ?? null,
            ],
            LotteryDraw::GAME_EUROJACKPOT => [
                'draw_date' => $drawDate,
                'numbers' => $this->requireNumberCount($numberGroups['main'] ?? [], 5, 'EuroJackpot Gewinnzahlen'),
                'bonus_numbers' => [
                    'euro_numbers' => $this->requireNumberCount($numberGroups['euro_numbers'] ?? [], 2, 'EuroJackpot Eurozahlen'),
                ],
                'stake_cents' => $tables[0]['stake_cents'] ?? null,
                'prize_classes' => $tables[0]['prize_classes'] ?? null,
            ],
            default => throw new InvalidArgumentException('Unbekannte Spielart.'),
        };
    }

    protected function xpathForHtml(string $content): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    protected function parseDrawNumberGroups(DOMXPath $xpath, DOMElement $drawContainer): array
    {
        $groups = [];
        $containers = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " DrawNumbersCollection__container ")]', $drawContainer);

        foreach ($containers ?: [] as $index => $container) {
            if (! $container instanceof DOMElement) {
                continue;
            }

            $label = mb_strtolower($this->nodeText($this->firstElement($xpath, './/*[contains(concat(" ", normalize-space(@class), " "), " DrawNumbersCollection__label ")]', $container)));
            $numbers = $this->numbersFromBallNodes($xpath, $container);

            if ($numbers === []) {
                continue;
            }

            if (str_contains($label, 'superzahl')) {
                $groups['superzahl'] = $numbers;
            } elseif (str_contains($label, 'euro')) {
                $groups['euro_numbers'] = $numbers;
            } elseif ($index === 0) {
                $groups['main'] = $numbers;
            } elseif (count($numbers) === 2) {
                $groups['euro_numbers'] = $numbers;
            }
        }

        $additionalGames = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " WinningNumbersAdditionalGame ")]', $drawContainer);

        foreach ($additionalGames ?: [] as $additionalGame) {
            if (! $additionalGame instanceof DOMElement) {
                continue;
            }

            $text = $this->nodeText($additionalGame);
            $digits = preg_replace('/\D+/', '', $text) ?? '';
            $logoText = mb_strtolower(implode(' ', [
                $this->attributeFromFirst($xpath, './/img', 'alt', $additionalGame),
                $this->attributeFromFirst($xpath, './/img', 'title', $additionalGame),
                $this->attributeFromFirst($xpath, './/img', 'src', $additionalGame),
            ]));

            if ($digits === '') {
                continue;
            }

            if (str_contains($logoText, 'spiel77') || str_contains($logoText, 'spiel 77')) {
                $groups['spiel77'] = $digits;
            } elseif (str_contains($logoText, 'super6') || str_contains($logoText, 'super 6')) {
                $groups['super6'] = $digits;
            }
        }

        return $groups;
    }

    protected function numbersFromBallNodes(DOMXPath $xpath, DOMElement $container): array
    {
        $numbers = [];
        $balls = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " LottoBall__circle ")]', $container);

        foreach ($balls ?: [] as $ball) {
            if (! $ball instanceof DOMElement) {
                continue;
            }

            $value = $ball->getAttribute('aria-label') ?: $this->nodeText($ball);

            if (is_numeric($value)) {
                $numbers[] = (int) $value;
            }
        }

        return $numbers;
    }

    protected function parseOddsTables(DOMXPath $xpath, DOMElement $drawContainer): array
    {
        $tables = [];
        $containers = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " OddsTableContainer ")]', $drawContainer);

        foreach ($containers ?: [] as $container) {
            if (! $container instanceof DOMElement) {
                continue;
            }

            $tables[] = [
                'title' => $this->nodeText($this->firstElement($xpath, './/*[contains(concat(" ", normalize-space(@class), " "), " OddsTableContainer__header ")]', $container)),
                'stake_cents' => $this->parseMoneyToCents($this->nodeText($this->firstElement($xpath, './/*[contains(concat(" ", normalize-space(@class), " "), " GameAmount ")]', $container))),
                'prize_classes' => $this->parseOddsTableRows($xpath, $container),
            ];
        }

        return $tables;
    }

    protected function parseOddsTableRows(DOMXPath $xpath, DOMElement $container): array
    {
        $rows = [];
        $rowNodes = $xpath->query('.//tbody/tr', $container);

        foreach ($rowNodes ?: [] as $rowNode) {
            if (! $rowNode instanceof DOMElement) {
                continue;
            }

            $cells = [];
            foreach ($xpath->query('./td', $rowNode) ?: [] as $cell) {
                $cells[] = $this->nodeText($cell);
            }

            if (count($cells) < 4) {
                continue;
            }

            $rows[] = [
                'class' => $this->parseIntOrNull($cells[0]),
                'match' => $cells[1],
                'winners' => $this->parseIntOrNull($cells[2]),
                'quote_cents' => $this->parseMoneyToCents($cells[3]),
                'jackpot' => mb_strtolower($cells[2]) === 'jackpot' || mb_strtolower($cells[3]) === 'jackpot',
            ];
        }

        return $rows;
    }

    protected function firstElement(DOMXPath $xpath, string $query, ?DOMNode $contextNode = null): ?DOMElement
    {
        $nodes = $contextNode ? $xpath->query($query, $contextNode) : $xpath->query($query);
        $node = $nodes?->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    protected function attributeFromFirst(DOMXPath $xpath, string $query, string $attribute, DOMNode $contextNode): string
    {
        $element = $this->firstElement($xpath, $query, $contextNode);

        return $element?->getAttribute($attribute) ?? '';
    }

    protected function nodeText(?DOMNode $node): string
    {
        return $this->clean($node?->textContent ?? '');
    }

    protected function requireNumberCount(array $numbers, int $count, string $label): array
    {
        if (count($numbers) !== $count) {
            throw new RuntimeException($label.' konnten nicht erkannt werden.');
        }

        return $numbers;
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

    protected function parseIntOrNull(mixed $value): ?int
    {
        $value = $this->clean($value);

        if ($value === '' || $value === '--' || mb_strtolower($value) === 'unbesetzt') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits === '' ? null : (int) $digits;
    }

    protected function parseMoneyToCents(mixed $value): ?int
    {
        $value = $this->clean($value);
        $value = preg_replace('/^Spieleinsatz:\s*/iu', '', $value) ?? $value;

        if ($value === '' || $value === '--' || mb_strtolower($value) === 'jackpot' || mb_strtolower($value) === 'unbesetzt') {
            return null;
        }

        $value = preg_replace('/[^\d,.-]+/u', '', $value) ?? $value;
        $normalized = str_replace(['.', ' '], '', $value);
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            return null;
        }

        return (int) round(((float) $normalized) * 100);
    }

    protected function clean(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && ! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\xC2\xA0", ' ', $value);

        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }
}
