<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_team_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('turn_number');
            $table->unsignedTinyInteger('team_turn_number');
            $table->foreignId('question_id')->constrained();
            $table->string('status');
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options');
            $table->boolean('is_correct')->nullable();
            $table->foreignId('challenge_id')->nullable()->constrained();
            $table->string('challenge_outcome')->nullable();
            $table->unsignedTinyInteger('points')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('challenge_opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['game_session_id', 'turn_number']);
            $table->unique(['game_session_id', 'question_id']);
            $table->unique(['game_session_id', 'challenge_id']);
            $table->unique(['game_team_id', 'team_turn_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_turns');
    }
};
