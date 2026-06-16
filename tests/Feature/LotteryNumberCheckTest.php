<?php

namespace Tests\Feature;

use App\Livewire\Admin\NumberCheckPage;
use App\Models\LotteryDraw;
use App\Models\LotteryNumberCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LotteryNumberCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_number_check_can_be_analyzed_and_saved(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'draw_date' => '2026-06-01',
            'numbers' => [1, 2, 3, 4, 5, 6],
            'bonus_numbers' => ['superzahl' => 7],
        ]);
        LotteryDraw::query()->create([
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'draw_date' => '2026-06-08',
            'numbers' => [7, 8, 9, 10, 11, 12],
            'bonus_numbers' => ['superzahl' => 3],
        ]);

        $this->actingAs($user);

        Livewire::test(NumberCheckPage::class)
            ->set('label', 'Testreihe')
            ->set('mainNumbersInput', '1, 2, 3, 4, 5, 6')
            ->set('bonusNumbersInput', '7')
            ->call('analyze')
            ->assertSet('currentAnalysis.main_numbers', [1, 2, 3, 4, 5, 6])
            ->call('save');

        $this->assertDatabaseHas('lottery_number_checks', [
            'user_id' => $user->id,
            'game' => LotteryDraw::GAME_LOTTO_6AUS49,
            'label' => 'Testreihe',
        ]);

        $this->assertSame([1, 2, 3, 4, 5, 6], LotteryNumberCheck::query()->first()?->main_numbers);
    }
}
