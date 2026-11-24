<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('nim', 'like', "%{$term}%")))
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->query('status') === 'pending', fn ($q) => $q->pendingApproval())
            ->orderByRaw('CASE WHEN self_registered = true AND approved_at IS NULL AND is_active = false THEN 0 ELSE 1 END')
            ->orderBy('role')->orderBy('name')
            ->paginate(25)->withQueryString();

        $pendingCount = User::pendingApproval()->count();

        return view('admin.users.index', compact('users', 'pendingCount'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $plain = $data['password'] ?? null;
        unset($data['password']);
        $nim = $data['nim'] ?? null;
        $password = $plain ?? ($nim ?: Str::password(10, symbols: false));
        User::create($data + ['password' => $password, 'must_change_password' => true]);

        $info = $plain ? 'sesuai yang diisi' : ($nim ? 'sama dengan NIM' : $password);

        return back()->with('success', "Akun {$data['name']} dibuat. Password awal: {$info}. Pengguna wajib menggantinya saat login pertama.");
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate($this->rules($user));
        unset($data['password']);
        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->with('error', 'Anda tidak dapat menurunkan peran akun Anda sendiri.');
        }
        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Akun diperbarui.');
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }
        $wasPending = $user->isPendingApproval();
        $user->update(['is_active' => ! $user->is_active]
            + ($wasPending ? ['approved_at' => now()] : []));

        if ($wasPending) {
            return back()->with('success', "Pendaftaran {$user->name} disetujui. Akun sudah dapat digunakan.");
        }

        return back()->with('success', 'Status akun '.$user->name.' diubah menjadi '.($user->is_active ? 'aktif' : 'nonaktif').'.');
    }

    /** Menolak pendaftaran yang belum disetujui (akun dihapus). */
    public function reject(User $user)
    {
        if (! $user->isPendingApproval()) {
            return back()->with('error', 'Hanya pendaftaran yang belum disetujui yang dapat ditolak.');
        }
        $user->delete();

        return back()->with('success', "Pendaftaran {$user->name} ditolak dan dihapus.");
    }

    public function resetPassword(User $user)
    {
        $password = Str::password(10, symbols: false);
        $user->forceFill(['password' => $password, 'must_change_password' => true])->save();

        return back()->with('success', "Password {$user->name} direset menjadi: {$password} (sampaikan secara pribadi).");
    }

    /** Impor CSV: kolom name,nim,email. Password awal = NIM, wajib diganti (FR-011). */
    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => Str::lower(trim($h, " \t\n\r\0\x0B\xEF\xBB\xBF")), fgetcsv($handle) ?: []);
        if (array_diff(['name', 'nim', 'email'], $header)) {
            return back()->with('error', 'Header CSV harus berisi: name,nim,email');
        }

        $created = 0;
        $errors = [];
        $line = 1;
        DB::transaction(function () use ($handle, $header, &$created, &$errors, &$line) {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (count(array_filter($row)) === 0) {
                    continue;
                }
                $data = array_combine($header, array_pad($row, count($header), ''));
                $data = array_map('trim', $data);
                $validator = validator($data, [
                    'name' => ['required', 'max:100'],
                    'nim' => ['required', 'max:20', 'unique:users,nim'],
                    'email' => ['required', 'email', 'max:150', 'unique:users,email'],
                ]);
                if ($validator->fails()) {
                    $errors[] = "Baris {$line}: ".implode(' ', $validator->errors()->all());
                    continue;
                }
                User::create([
                    'name' => $data['name'], 'nim' => $data['nim'], 'email' => $data['email'],
                    'password' => $data['nim'], 'role' => 'mahasiswa', 'must_change_password' => true,
                ]);
                $created++;
            }
        });
        fclose($handle);

        return back()->with('success', "{$created} akun mahasiswa diimpor (password awal = NIM).")
            ->with('import_errors', $errors);
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'nim' => ['nullable', 'string', 'max:20', Rule::unique('users', 'nim')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => [$user ? 'prohibited' : 'nullable', 'string', 'min:8'],
        ];
    }
}
