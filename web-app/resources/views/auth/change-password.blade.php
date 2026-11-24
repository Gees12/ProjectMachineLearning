@extends('layouts.app')
@section('title', 'Ganti Password')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Ganti Password</h1>
                <form method="POST" action="{{ route('password.change') }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Password saat ini</label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password baru</label>
                        <input type="password" name="password" class="form-control" required autocomplete="new-password">
                        <div class="form-text">Minimal 8 karakter, berisi huruf dan angka.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ulangi password baru</label>
                        <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                    </div>
                    <button class="btn btn-di">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
