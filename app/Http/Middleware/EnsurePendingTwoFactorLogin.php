<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePendingTwoFactorLogin
{
    /**
     * Halaman challenge 2FA hanya boleh diakses jika sesi memuat ID pengguna
     * yang sudah lulus verifikasi kata sandi (belum login penuh).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return redirect()->route('admin.dashboard');
        }

        if (! $request->session()->has('two_factor_login.id')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
