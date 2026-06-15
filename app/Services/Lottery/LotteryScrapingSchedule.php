<?php

namespace App\Services\Lottery;

use App\Models\Setting;

class LotteryScrapingSchedule
{
    public const DEFAULT_TIME = '07:15';

    public const DEFAULT_WEEKDAYS = [0, 1, 2, 3, 4, 5, 6];

    public function settings(): array
    {
        $settings = Setting::getValue('lottery', 'scraping_schedule');
        $settings = is_array($settings) ? $settings : [];

        return [
            'enabled' => (bool) ($settings['enabled'] ?? true),
            'time' => $this->normalizeTime($settings['time'] ?? self::DEFAULT_TIME),
            'weekdays' => $this->normalizeWeekdays($settings['weekdays'] ?? self::DEFAULT_WEEKDAYS),
        ];
    }

    public function save(bool $enabled, string $time, array $weekdays): void
    {
        Setting::setValue('lottery', 'scraping_schedule', [
            'enabled' => $enabled,
            'time' => $this->normalizeTime($time),
            'weekdays' => $this->normalizeWeekdays($weekdays),
        ]);
    }

    public function weekdayLabels(): array
    {
        return [
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag',
            0 => 'Sonntag',
        ];
    }

    public function summary(array $settings): string
    {
        if (! ($settings['enabled'] ?? false)) {
            return 'Automatische Abfrage deaktiviert';
        }

        $labels = $this->weekdayLabels();
        $weekdays = $this->normalizeWeekdays($settings['weekdays'] ?? []);
        $dayText = count($weekdays) === 7
            ? 'taeglich'
            : collect($weekdays)->map(fn (int $day): string => $labels[$day])->implode(', ');

        return $dayText.' um '.$this->normalizeTime($settings['time'] ?? self::DEFAULT_TIME).' Uhr';
    }

    protected function normalizeTime(mixed $time): string
    {
        $time = (string) $time;

        return preg_match('/^\d{2}:\d{2}$/', $time) === 1 ? $time : self::DEFAULT_TIME;
    }

    protected function normalizeWeekdays(mixed $weekdays): array
    {
        $weekdays = is_array($weekdays) ? $weekdays : self::DEFAULT_WEEKDAYS;
        $weekdays = array_values(array_unique(array_map('intval', $weekdays)));
        $weekdays = array_values(array_filter($weekdays, fn (int $day): bool => $day >= 0 && $day <= 6));

        sort($weekdays);

        return $weekdays === [] ? self::DEFAULT_WEEKDAYS : $weekdays;
    }
}
