<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian: ->middleware('role:admin') atau 'role:pengelola,admin' (FR-004). */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['login' => 'Akun Anda tidak aktif.']);
        }

        if (! $user || ! $user->hasRole(...$roles)) {
            abort(403, 'Akses ditolak. Halaman ini tidak tersedia untuk peran Anda.');
        }

        return $next($request);
    }
}
