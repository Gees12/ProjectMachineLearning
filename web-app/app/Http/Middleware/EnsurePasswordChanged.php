<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Wajib mengganti password awal sebelum memakai aplikasi (FR-005). */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            return redirect()->route('password.edit')
                ->with('warning', 'Silakan ganti password awal Anda terlebih dahulu.');
        }

        return $next($request);
    }
}
