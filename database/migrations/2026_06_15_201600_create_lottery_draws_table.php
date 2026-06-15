<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_draws', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lottery_import_id')->nullable()->constrained('lottery_imports')->nullOnDelete();
            $table->string('game', 40)->index();
            $table->date('draw_date')->index();
            $table->string('draw_identifier')->nullable()->index();
            $table->json('numbers');
            $table->json('bonus_numbers')->nullable();
            $table->unsignedBigInteger('stake_cents')->nullable();
            $table->json('prize_classes')->nullable();
            $table->string('source_file')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['game', 'draw_date'], 'lottery_draws_game_draw_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_draws');
    }
};
