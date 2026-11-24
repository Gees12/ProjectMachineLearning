@extends('layouts.app')
@section('title', 'Masuk')
@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4">
            <h1 class="h3 text-di fw-bold"><i class="bi bi-building"></i> DormInsight</h1>
            <p class="text-muted mb-0">Crystal Dormitory · Universitas Klabat</p>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('login.attempt') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="login">Email / NIM</label>
                        <input id="login" name="login" value="{{ old('login') }}" class="form-control @error('login') is-invalid @enderror" required autofocus autocomplete="username">
                        @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-di w-100">Masuk</button>
                </form>
            </div>
        </div>
        @if (config('dorminsight.registration.enabled'))
            <p class="text-center small mt-3 mb-1">Belum punya akun? <a href="{{ route('register') }}">Daftar dengan NIM</a></p>
        @endif
        <p class="text-center text-muted small mt-1">Lupa password? Hubungi admin asrama.</p>
    </div>
</div>
@endsection
