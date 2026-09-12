<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi rute ke peran tertentu. Dipakai sebagai alias "peran".
 *
 *   Route::middleware('peran:admin')->group(...)
 */
class PastikanPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        abort_unless($user && in_array($user->peran, $peran, true), 403);

        return $next($request);
    }
}
