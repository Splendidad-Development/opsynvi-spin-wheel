<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sessions created before games were per device have no owner and can no longer be resumed.
        DB::table('game_sessions')->whereNotNull('is_current')->update(['is_current' => null]);

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropUnique(['is_current']);
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            // The browser that owns the game; each player has at most one current session.
            $table->uuid('player_id')->nullable()->after('id');
            $table->unique(['player_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('game_sessions')->whereNotNull('is_current')->update(['is_current' => null]);

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropUnique(['player_id', 'is_current']);
            $table->dropColumn('player_id');
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->unique('is_current');
        });
    }
};
