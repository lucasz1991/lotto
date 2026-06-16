<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_number_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('game', 40)->index();
            $table->string('label')->nullable();
            $table->json('main_numbers');
            $table->json('bonus_numbers')->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('rating', 40)->default('Keine Daten');
            $table->json('analysis')->nullable();
            $table->timestamp('last_evaluated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_number_checks');
    }
};
