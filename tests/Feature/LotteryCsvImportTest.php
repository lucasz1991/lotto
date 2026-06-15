<?php

namespace Tests\Feature;

use App\Models\LotteryDraw;
use App\Services\Lottery\LotteryCsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_lotto_6aus49_csv_can_be_imported(): void
    {
        $path = $this->writeCsv(<<<'CSV'
     Datum; ;        Gewinnzahlen;ZZ;S;Spiel77;Super6;  Spieleinsatz;Anz. Kl. 1;  Quote Kl. 1;Anz. Kl. 2;  Quote Kl. 2;Anz. Kl. 3;  Quote Kl. 3;Anz. Kl. 4;  Quote Kl. 4;Anz. Kl. 5;  Quote Kl. 5;Anz. Kl. 6;  Quote Kl. 6;Anz. Kl. 7;  Quote Kl. 7;Anz. Kl. 8;  Quote Kl. 8;Anz. Kl. 9;  Quote Kl. 9
03.01.2018; ;45;10;34;31;15;35;--;--;8;8977180;373159; 22.909.485,00;   Jackpot; 6.803.818,90;   Jackpot;   861.581,50;        36;    11.966,40;       308;     4.196,00;     1.952;       220,60;    18.511;        46,50;    36.934;        23,30;   330.060;        11,70;   274.544;         5,00
CSV);

        $import = app(LotteryCsvImportService::class)->import($path, 'LOTTO_ab_2018.csv');

        $draw = LotteryDraw::query()->firstOrFail();

        $this->assertSame(LotteryDraw::GAME_LOTTO_6AUS49, $import->game);
        $this->assertSame(1, $import->rows_imported);
        $this->assertSame([45, 10, 34, 31, 15, 35], $draw->numbers);
        $this->assertSame(8, $draw->bonus_numbers['superzahl']);
        $this->assertSame('8977180', $draw->bonus_numbers['spiel77']);
        $this->assertSame(2290948500, $draw->stake_cents);
        $this->assertTrue($draw->prize_classes[0]['jackpot']);
    }

    public function test_eurojackpot_csv_can_be_imported(): void
    {
        $path = $this->writeCsv(<<<'CSV'
     Datum;      5 aus 50;   EZ;  Spieleinsatz; Anz. Kl. 1;   Quote Kl. 1; Anz. Kl. 2;   Quote Kl. 2; Anz. Kl. 3;   Quote Kl. 3; Anz. Kl. 4;   Quote Kl. 4; Anz. Kl. 5;   Quote Kl. 5; Anz. Kl. 6;   Quote Kl. 6; Anz. Kl. 7;   Quote Kl. 7; Anz. Kl. 8;   Quote Kl. 8; Anz. Kl. 9;   Quote Kl. 9;Anz. Kl. 10;  Quote Kl. 10;Anz. Kl. 11;  Quote Kl. 11;Anz. Kl. 12;  Quote Kl. 12
05.01.2018;40; 2;38;45; 7;10; 7; 42.621.542,00;         --;            --;          4;    452.853,80;          8;     79.915,30;         32;      6.659,60;        662;        289,70;      1.186;        125,70;      1.578;         81,00;     23.850;         27,60;     30.358;         21,00;     54.020;         16,90;    128.698;         12,90;    472.493;          8,60
CSV);

        $import = app(LotteryCsvImportService::class)->import($path, 'EJ_ab_2018.csv');

        $draw = LotteryDraw::query()->firstOrFail();

        $this->assertSame(LotteryDraw::GAME_EUROJACKPOT, $import->game);
        $this->assertSame(1, $import->rows_imported);
        $this->assertSame([40, 2, 38, 45, 7], $draw->numbers);
        $this->assertSame([10, 7], $draw->bonus_numbers['euro_numbers']);
        $this->assertSame(4262154200, $draw->stake_cents);
        $this->assertSame(4, $draw->prize_classes[1]['winners']);
        $this->assertSame(45285380, $draw->prize_classes[1]['quote_cents']);
    }

    private function writeCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lottery-csv-');
        file_put_contents($path, $content.PHP_EOL);

        return $path;
    }
}
