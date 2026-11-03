<?php

namespace App\Http\Middleware;

use App\Models\GameSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifies the browser playing the game with a long-lived, encrypted cookie so every device
 * gets its own game, and rejects requests for a session that belongs to another device.
 */
class IdentifyGamePlayer
{
    public const COOKIE = 'game_player';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $playerId = $request->cookie(self::COOKIE);
        $isNewPlayer = ! is_string($playerId) || ! Str::isUuid($playerId);

        if ($isNewPlayer) {
            $playerId = (string) Str::uuid();
        }

        $request->attributes->set(self::COOKIE, $playerId);

        $gameSession = $request->route('gameSession');

        if ($gameSession instanceof GameSession && $gameSession->player_id !== $playerId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $response = $next($request);

        if ($isNewPlayer) {
            $response->headers->setCookie(cookie()->forever(self::COOKIE, $playerId));
        }

        return $response;
    }

    /**
     * The player identified for the given request.
     */
    public static function playerId(Request $request): string
    {
        return $request->attributes->get(self::COOKIE);
    }
}
