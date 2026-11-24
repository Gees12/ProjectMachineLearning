@extends('layouts.app')
@section('title', 'Manajemen Akun')
@section('content')
<h1 class="h4 mb-3">Manajemen Akun</h1>
@if ($pendingCount > 0)
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <span><i class="bi bi-person-plus"></i> <strong>{{ $pendingCount }}</strong> pendaftaran menunggu persetujuan. Pastikan nama dan NIM sesuai daftar penghuni Crystal Dormitory sebelum menyetujui.</span>
        <a href="{{ route('admin.users.index', ['status' => 'pending']) }}" class="btn btn-sm btn-warning">Lihat</a>
    </div>
@endif
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h6">Tambah akun</h2>
            <form method="POST" action="{{ route('admin.users.store') }}" class="row g-2">
                @csrf
                <div class="col-md-6"><input name="name" class="form-control" placeholder="Nama lengkap" value="{{ old('name') }}" required></div>
                <div class="col-md-6"><input name="email" type="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required></div>
                <div class="col-md-4"><input name="nim" class="form-control" placeholder="NIM (mahasiswa)" value="{{ old('nim') }}"></div>
                <div class="col-md-4">
                    <select name="role" class="form-select">
                        @foreach (\App\Models\User::ROLES as $r)<option value="{{ $r }}" @selected(old('role') === $r)>{{ ucfirst($r) }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4"><input name="password" type="password" class="form-control" placeholder="Password (opsional)"></div>
                <div class="col-12 small text-muted">Jika password kosong: password awal = NIM, atau dibuat acak bila tanpa NIM. Pengguna wajib menggantinya saat login pertama.</div>
                <div class="col-12"><button class="btn btn-di btn-sm">Simpan</button></div>
            </form>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h6">Impor mahasiswa dari CSV</h2>
            <p class="small text-muted mb-2">Header: <code>name,nim,email</code>. Password awal = NIM.</p>
            <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="d-flex gap-2">
                @csrf
                <input type="file" name="file" accept=".csv,text/csv" class="form-control form-control-sm" required>
                <button class="btn btn-outline-primary btn-sm">Impor</button>
            </form>
        </div></div>
    </div>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-5"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari nama, email, atau NIM"></div>
    <div class="col-md-3">
        <select name="role" class="form-select form-select-sm">
            <option value="">Semua peran</option>
            @foreach (\App\Models\User::ROLES as $r)<option value="{{ $r }}" @selected(request('role') === $r)>{{ ucfirst($r) }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">Semua status</option>
            <option value="pending" @selected(request('status') === 'pending')>Menunggu persetujuan</option>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-sm btn-outline-secondary w-100">Filter</button></div>
</form>

<div class="card border-0 shadow-sm"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Nama</th><th>NIM</th><th>Email</th><th>Peran</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
    @forelse ($users as $user)
        <tr>
            <td>{{ $user->name }}</td><td>{{ $user->nim ?? '-' }}</td><td>{{ $user->email }}</td>
            <td><span class="badge bg-secondary">{{ ucfirst($user->role) }}</span></td>
            <td>
                @if ($user->isPendingApproval())<span class="badge bg-warning text-dark">Menunggu persetujuan</span>
                @elseif ($user->is_active)<span class="badge bg-success">Aktif</span>
                @else<span class="badge bg-light text-dark">Nonaktif</span>@endif
                @if ($user->self_registered)<div class="small text-muted">Daftar mandiri {{ $user->created_at?->format('d M Y') }}</div>@endif
            </td>
            <td class="text-end text-nowrap">
                @if ($user->isPendingApproval())
                    <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Setujui</button></form>
                    <form method="POST" action="{{ route('admin.users.reject', $user) }}" class="d-inline" onsubmit="return confirm('Tolak dan hapus pendaftaran ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Tolak</button></form>
                @else
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Ubah</a>
                <form method="POST" action="{{ route('admin.users.reset', $user) }}" class="d-inline" onsubmit="return confirm('Reset password akun ini?')">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning">Reset</button></form>
                <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-{{ $user->is_active ? 'danger' : 'success' }}">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada akun.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>
<div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>
@endsection
