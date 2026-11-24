@extends('layouts.app')
@section('title', 'Daftar Akun')
@section('content')
<div class="row justify-content-center mt-4">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <h1 class="h3 text-di fw-bold"><i class="bi bi-building"></i> DormInsight</h1>
            <p class="text-muted mb-0">Daftar akun penghuni Crystal Dormitory</p>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('register.store') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="name">Nama lengkap</label>
                        <input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autofocus autocomplete="name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nim">NIM</label>
                        <input id="nim" name="nim" value="{{ old('nim') }}" class="form-control @error('nim') is-invalid @enderror" required inputmode="numeric" autocomplete="username">
                        @error('nim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="email"
                               @if($emailDomain) placeholder="nama@{{ ltrim($emailDomain, '@') }}" @endif>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($emailDomain)<div class="form-text">Gunakan email @{{ ltrim($emailDomain, '@') }}.</div>@endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Minimal 8 karakter, berisi huruf dan angka.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Ulangi password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                    </div>
                    @if (config('dorminsight.registration.require_approval'))
                        <div class="alert alert-info small py-2"><i class="bi bi-info-circle"></i> Akun baru perlu disetujui admin asrama sebelum dapat digunakan.</div>
                    @endif
                    <button class="btn btn-di w-100">Daftar</button>
                </form>
            </div>
        </div>
        <p class="text-center small mt-3">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </div>
</div>
@endsection
