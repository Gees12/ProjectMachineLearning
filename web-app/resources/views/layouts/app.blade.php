<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DormInsight') · DormInsight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --di-primary: #1F4E79; }
        body { background: #F8F9FA; }
        .navbar-di { background: var(--di-primary); }
        .btn-di { background: var(--di-primary); border-color: var(--di-primary); color: #fff; }
        .btn-di:hover { background: #163a5a; color: #fff; }
        .text-di { color: var(--di-primary); }
        h1, h2, h3, .h4, .h5, .h6 { text-wrap: balance; }
        p, li { text-wrap: pretty; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
        .stat-card .label { font-size: .8125rem; color: var(--bs-secondary-color); }
        .stat-card .value { font-size: 1.75rem; font-weight: 600; line-height: 1.2; color: var(--di-primary); font-variant-numeric: tabular-nums; }
        .stat-card .hint { font-size: .8125rem; color: var(--bs-secondary-color); }
        .chart-box { position: relative; height: 280px; }
        .legend-dot { display: inline-block; width: .625rem; height: .625rem; border-radius: 50%; vertical-align: baseline; }
        .risk-rendah { background: #198754; } .risk-sedang { background: #FFC107; } .risk-tinggi { background: #DC3545; }
        .progress-bar { transition: none; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0s !important; transition-duration: 0s !important; scroll-behavior: auto !important; }
        }
    </style>
    @stack('head')
</head>
<body>
@auth
<nav class="navbar navbar-expand-lg navbar-dark navbar-di mb-4">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('home') }}"><i class="bi bi-building"></i> DormInsight</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Buka menu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                @if(auth()->user()->hasRole('mahasiswa'))
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('survey.*')) active @endif" href="{{ route('survey.index') }}">Survei</a></li>
                @endif
                @if(auth()->user()->hasRole('pengelola', 'admin'))
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('dashboard.compare')) active @endif" href="{{ route('dashboard.compare') }}">Perbandingan</a></li>
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('simulation.*')) active @endif" href="{{ route('simulation.index') }}">Simulasi</a></li>
                @endif
                @if(auth()->user()->hasRole('admin'))
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.surveys.*', 'admin.responses.*', 'admin.analyses.*')) active @endif" href="{{ route('admin.surveys.index') }}">Survei &amp; Data</a></li>
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.users.*')) active @endif" href="{{ route('admin.users.index') }}">Akun</a></li>
                @endif
            </ul>
            <div class="d-flex align-items-center gap-3 text-white small">
                <span><i class="bi bi-person-circle"></i> {{ auth()->user()->name }} <span class="badge bg-light text-dark">{{ ucfirst(auth()->user()->role) }}</span></span>
                <a href="{{ route('password.edit') }}" class="link-light">Ganti password</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right"></i> Keluar</button></form>
            </div>
        </div>
    </div>
</nav>
@endauth
<main class="container pb-5">
    @include('partials.alerts')
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
