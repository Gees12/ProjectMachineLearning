<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($data['login']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'login' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        $field = str_contains($data['login'], '@') ? 'email' : 'nim';
        $user = User::where($field, $data['login'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['login' => 'Email/NIM atau password salah.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['login' => $user->isPendingApproval()
                ? 'Akun Anda masih menunggu persetujuan admin asrama.'
                : 'Akun Anda tidak aktif. Hubungi admin.']);
        }

        RateLimiter::clear($key);
        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route($user->homeRoute()));
    }

    public function showRegister()
    {
        abort_unless(config('dorminsight.registration.enabled'), 404);

        return view('auth.register', ['emailDomain' => config('dorminsight.registration.email_domain')]);
    }

    /** Pendaftaran mandiri mahasiswa dengan NIM dan email (FR-015 sampai FR-018). */
    public function register(Request $request)
    {
        abort_unless(config('dorminsight.registration.enabled'), 404);
        $domain = config('dorminsight.registration.email_domain');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nim' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z]+$/', 'unique:users,nim'],
            'email' => array_filter(['required', 'email', 'max:150', 'unique:users,email',
                $domain ? 'ends_with:@'.ltrim($domain, '@') : null]),
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'nim.unique' => 'NIM ini sudah terdaftar. Jika Anda belum pernah mendaftar, hubungi admin asrama.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'email.ends_with' => 'Gunakan email dengan domain @'.ltrim((string) $domain, '@').'.',
            'nim.regex' => 'NIM hanya boleh berisi huruf dan angka.',
        ]);

        $approval = config('dorminsight.registration.require_approval');
        $user = User::create([
            'name' => $data['name'],
            'nim' => $data['nim'],
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'role' => 'mahasiswa',
            'is_active' => ! $approval,
            'must_change_password' => false,
            'self_registered' => true,
            'approved_at' => $approval ? null : now(),
        ]);

        if ($approval) {
            return redirect()->route('login')->with('success',
                'Pendaftaran berhasil. Akun Anda akan aktif setelah disetujui admin asrama.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->homeRoute())->with('success', 'Pendaftaran berhasil. Selamat datang!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function editPassword()
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $request->input('password'), 'must_change_password' => false])->save();

        return redirect()->route($user->homeRoute())->with('success', 'Password berhasil diganti.');
    }
}
