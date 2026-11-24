@extends('layouts.app')
@section('title', 'Ubah Akun')
@section('content')
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h1 class="h5 mb-3">Ubah Akun</h1>
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf @method('PUT')
        <div class="mb-3"><label class="form-label">Nama</label><input name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
        <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
        <div class="mb-3"><label class="form-label">NIM</label><input name="nim" class="form-control" value="{{ old('nim', $user->nim) }}"></div>
        <div class="mb-3"><label class="form-label">Peran</label>
            <select name="role" class="form-select">
                @foreach (\App\Models\User::ROLES as $r)<option value="{{ $r }}" @selected(old('role', $user->role) === $r)>{{ ucfirst($r) }}</option>@endforeach
            </select>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button class="btn btn-di">Simpan</button>
    </form>
</div></div>
</div></div>
@endsection
