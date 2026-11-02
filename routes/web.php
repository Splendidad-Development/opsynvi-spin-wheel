<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\GameSessionController;
use App\Http\Controllers\GameTurnController;
use Illuminate\Support\Facades\Route;

Route::get('/', GameController::class)->name('home');

Route::prefix('api/game')->name('game.')->group(function () {
    Route::get('current', [GameSessionController::class, 'show'])->name('current');
    Route::post('sessions', [GameSessionController::class, 'store'])->name('sessions.store');
    Route::post('sessions/{gameSession}/restart', [GameSessionController::class, 'restart'])->name('sessions.restart');

    Route::prefix('sessions/{gameSession}/turns')->name('turns.')->group(function () {
        Route::post('/', [GameTurnController::class, 'store'])->name('store');
        Route::post('{gameTurn}/answer', [GameTurnController::class, 'answer'])->name('answer');
        Route::post('{gameTurn}/challenge', [GameTurnController::class, 'challenge'])->name('challenge');
        Route::post('{gameTurn}/challenge/resolve', [GameTurnController::class, 'resolveChallenge'])->name('challenge.resolve');
        Route::post('{gameTurn}/complete', [GameTurnController::class, 'complete'])->name('complete');
    });
});
