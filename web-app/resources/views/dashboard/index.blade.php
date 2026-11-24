@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@if (! $d)
    <div class="card border-0 shadow-sm"><div class="card-body text-center p-5">
        <i class="bi bi-bar-chart fs-1 text-muted" aria-hidden="true"></i>
        <h1 class="h5 mt-3">Belum ada data survei</h1>
        <p class="text-muted">Dashboard akan terisi setelah survei dibuka dan mahasiswa mulai mengisi.</p>
        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.surveys.index') }}" class="btn btn-di btn-sm">Kelola survei</a>
        @else
            <p class="small text-muted mb-0">Hubungi admin untuk membuka survei.</p>
        @endif
    </div></div>
@else
@php
    $run = $d['run'];
    $survey = $d['survey'];
    $isAdmin = auth()->user()->hasRole('admin');
    $population = config('dorminsight.population');
    $minValid = config('dorminsight.min_valid_responses');
    $kurang = max(0, $minValid - $d['total_valid']);
    $showRisk = $run && ! $d['hidden'] && array_sum($d['risk']) > 0;
    $riskMeta = ['rendah' => 'Rendah', 'sedang' => 'Sedang', 'tinggi' => 'Tinggi'];
@endphp

{{-- Judul dan konteks --}}
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h1 class="h4 mb-1">Dashboard Kepuasan &amp; Retensi</h1>
        <p class="small text-muted mb-0">
            {{ $survey->title }} · {{ $survey->statusLabel() }}
            @if ($run) · Analisis terakhir <span class="tabular-nums">{{ $run->run_at->format('d M Y H:i') }}</span>@endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.pdf', $survey) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Unduh PDF</a>
        <a href="{{ route('reports.excel', $survey) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Unduh Excel</a>
    </div>
</div>

{{-- Filter --}}
<form method="GET" class="card border-0 shadow-sm mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-md-5"><label for="f-survey" class="form-label small mb-1">Survei</label>
        <select id="f-survey" name="survey" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach ($surveys as $s)<option value="{{ $s->id }}" @selected($s->id === $survey->id)>{{ $s->title }} ({{ $s->statusLabel() }})</option>@endforeach
        </select>
    </div>
    <div class="col-6 col-md-3"><label for="f-angkatan" class="form-label small mb-1">Angkatan</label>
        <select id="f-angkatan" name="angkatan" class="form-select form-select-sm">
            <option value="">Semua</option>
            @foreach ($segments['angkatan'] as $v => $l)<option value="{{ $v }}" @selected(request('angkatan') === (string) $v)>{{ $l }}</option>@endforeach
        </select>
    </div>
    <div class="col-6 col-md-2"><label for="f-lama" class="form-label small mb-1">Lama tinggal</label>
        <select id="f-lama" name="lama_tinggal" class="form-select form-select-sm">
            <option value="">Semua</option>
            @foreach ($segments['lama_tinggal'] as $v => $l)<option value="{{ $v }}" @selected(request('lama_tinggal') === (string) $v)>{{ $l }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-sm btn-di flex-grow-1">Terapkan</button>
        @if ($d['filters'])
            <a href="{{ route('dashboard', ['survey' => $survey->id]) }}" class="btn btn-sm btn-outline-secondary" aria-label="Hapus filter"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
        @endif
    </div>
</div></form>

@include('partials.experimental-banner', ['run' => $run])

@if ($d['hidden'])
    <div class="alert alert-secondary d-flex gap-2"><i class="bi bi-shield-lock" aria-hidden="true"></i>
        <div>Segmen ini memiliki kurang dari {{ config('dorminsight.min_segment_size') }} responden. Statistik disembunyikan untuk melindungi anonimitas.
            <a href="{{ route('dashboard', ['survey' => $survey->id]) }}" class="alert-link">Tampilkan semua responden</a></div>
    </div>
@endif

{{-- Kartu ringkasan --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm stat-card h-100"><div class="card-body">
        <div class="label"><i class="bi bi-people" aria-hidden="true"></i> Responden valid</div>
        <div class="value">{{ $d['n'] }}@if ($d['filters'])<span class="fs-6 text-muted fw-normal"> / {{ $d['total_valid'] }}</span>@endif</div>
        <div class="hint">dari {{ $population }} penghuni</div>
    </div></div></div>

    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm stat-card h-100"><div class="card-body">
        <div class="label"><i class="bi bi-graph-up" aria-hidden="true"></i> Tingkat respons</div>
        <div class="value">{{ round($d['response_rate'] * 100) }}%</div>
        <div class="hint">
            @if ($kurang > 0)
                Kurang <span class="tabular-nums">{{ $kurang }}</span> lagi untuk minimum analisis ({{ $minValid }})
            @else
                <i class="bi bi-check-circle text-success" aria-hidden="true"></i> Minimum analisis ({{ $minValid }}) tercapai
            @endif
        </div>
    </div></div></div>

    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm stat-card h-100"><div class="card-body">
        <div class="label"><i class="bi bi-emoji-smile" aria-hidden="true"></i> Kepuasan rata-rata</div>
        <div class="value">{{ $d['avg_kep_umum'] !== null ? number_format($d['avg_kep_umum'], 1, ',', '.') : '-' }}<span class="fs-6 text-muted fw-normal"> / 5</span></div>
        <div class="hint">Pertanyaan kepuasan umum</div>
    </div></div></div>

    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm stat-card h-100"><div class="card-body">
        <div class="label"><i class="bi bi-door-open" aria-hidden="true"></i> Menyatakan ingin keluar</div>
        <div class="value">{{ $d['stated_exit_rate'] !== null ? round($d['stated_exit_rate'] * 100).'%' : '-' }}</div>
        <div class="hint">
            @if ($showRisk)
                Model: <span class="tabular-nums">{{ round($d['risk_pct']['tinggi'] * 100) }}%</span> berisiko tinggi
            @else
                Jawaban "Ya" pada pertanyaan langsung
            @endif
        </div>
    </div></div></div>
</div>

{{-- Grafik --}}
<div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
        <h2 class="h6 mb-1">Faktor paling berpengaruh terhadap keinginan keluar</h2>
        @if (! $run)
            <div class="text-center py-5">
                <i class="bi bi-cpu fs-2 text-muted" aria-hidden="true"></i>
                <p class="text-muted small mt-2">Analisis ML belum dijalankan untuk survei ini.</p>
                @if ($isAdmin)
                    <a href="{{ route('admin.analyses.index', $survey) }}" class="btn btn-sm btn-di">Jalankan analisis</a>
                @else
                    <p class="small text-muted mb-0">Minta admin menjalankan analisis.</p>
                @endif
            </div>
        @else
            <p class="small text-muted mb-2">
                <span class="legend-dot me-1" style="background:#1F4E79"></span>Stabil
                <span class="legend-dot ms-3 me-1" style="background:#9AA5B1"></span>Belum stabil
            </p>
            <div class="chart-box"><canvas id="shapChart" role="img" aria-label="Grafik batang besar pengaruh tiap faktor terhadap keinginan keluar"></canvas></div>
            @if ($d['insights'])
                <ol class="small mt-3 mb-2 ps-3">@foreach ($d['insights'] as $line)<li class="mb-1">{{ $line }}</li>@endforeach</ol>
            @endif
            <details class="small text-muted">
                <summary>Cara membaca grafik ini</summary>
                <p class="mt-2 mb-0">Panjang batang = besar pengaruh faktor terhadap perkiraan keinginan keluar (nilai SHAP).
                    "Stabil" berarti faktor tetap masuk 3 besar pada sedikitnya {{ config('dorminsight.stable_top3_frequency') * 100 }}% uji ulang dengan sampel berbeda.
                    Faktor dominan dihitung dari seluruh responden, tidak ikut filter segmen.</p>
            </details>
        @endif
    </div></div></div>

    <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
        <h2 class="h6 mb-1">Skor rata-rata per faktor (1 sampai 5)</h2>
        @if ($d['hidden'] || $d['n'] === 0)
            <div class="text-center py-5">
                <i class="bi bi-funnel fs-2 text-muted" aria-hidden="true"></i>
                <p class="text-muted small mt-2">Tidak ada data untuk ditampilkan.</p>
                @if ($d['filters'])
                    <a href="{{ route('dashboard', ['survey' => $survey->id]) }}" class="btn btn-sm btn-outline-secondary">Hapus filter</a>
                @endif
            </div>
        @else
            <p class="small text-muted mb-2">
                <span class="legend-dot me-1 risk-tinggi"></span>Perlu perhatian (&lt; 3)
                <span class="legend-dot ms-3 me-1 risk-sedang"></span>Cukup (3 sampai 3,5)
                <span class="legend-dot ms-3 me-1 risk-rendah"></span>Baik (&ge; 3,5)
            </p>
            <div class="chart-box"><canvas id="factorChart" role="img" aria-label="Grafik batang skor rata-rata tiap faktor, diurutkan dari yang terendah"></canvas></div>
            <p class="small text-muted mt-2 mb-0">Diurutkan dari skor terendah, jadi faktor teratas adalah prioritas perbaikan.</p>
        @endif
    </div></div></div>
</div>

{{-- Distribusi risiko --}}
@if ($showRisk)
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2">
        <h2 class="h6 mb-0">Distribusi kecenderungan keluar (perkiraan model)</h2>
        <span class="small text-muted tabular-nums">
            Pernyataan langsung {{ $d['stated_exit_rate'] !== null ? round($d['stated_exit_rate'] * 100).'%' : '-' }}
            · Prediksi risiko tinggi {{ round($d['risk_pct']['tinggi'] * 100) }}%
        </span>
    </div>
    <div class="progress mb-2" style="height: 24px" role="img"
         aria-label="Rendah {{ round($d['risk_pct']['rendah'] * 100) }}%, sedang {{ round($d['risk_pct']['sedang'] * 100) }}%, tinggi {{ round($d['risk_pct']['tinggi'] * 100) }}%">
        @foreach ($riskMeta as $lvl => $label)
            @if ($d['risk_pct'][$lvl] > 0)
                <div class="progress-bar risk-{{ $lvl }} tabular-nums {{ $lvl === 'sedang' ? 'text-dark' : '' }}" style="width: {{ $d['risk_pct'][$lvl] * 100 }}%">
                    @if ($d['risk_pct'][$lvl] >= 0.1){{ round($d['risk_pct'][$lvl] * 100) }}%@endif
                </div>
            @endif
        @endforeach
    </div>
    <div class="d-flex flex-wrap gap-3 small tabular-nums mb-2">
        @foreach ($riskMeta as $lvl => $label)
            <span><span class="legend-dot me-1 risk-{{ $lvl }}"></span>{{ $label }}: {{ $d['risk'][$lvl] }} responden ({{ round($d['risk_pct'][$lvl] * 100) }}%)</span>
        @endforeach
    </div>
    <p class="small text-muted mb-0">Rendah &lt; {{ ($run->metrics['risk_thresholds']['sedang'] ?? 0.4) * 100 }}%, Sedang sampai &lt; {{ ($run->metrics['risk_thresholds']['tinggi'] ?? 0.7) * 100 }}%, Tinggi di atasnya.
        Selisih antara pernyataan langsung dan prediksi menunjukkan ketidakpuasan yang tidak diungkapkan, atau sebaliknya. Hanya ditampilkan secara agregat.</p>
</div></div>
@endif

<p class="small text-muted">
    @if ($run)
        Model <code>{{ $run->model_version }}</code> ({{ $run->algorithm }}) · F1 kelas ingin keluar: <span class="tabular-nums">{{ $run->metrics['f1_keluar'] ?? '-' }}</span>
        (baseline <span class="tabular-nums">{{ $run->metrics['baseline_f1_keluar'] ?? '-' }}</span>) ·
    @endif
    <i class="bi bi-info-circle" aria-hidden="true"></i> Hasil bersifat agregat dan merupakan perkiraan model, bukan penilaian terhadap mahasiswa tertentu.
</p>
@endif
@endsection

@if ($d)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (() => {
        const shap = @json($d['shap']);
        const factorMeans = @json($d['factor_means']);
        const labels = @json($d['labels']);
        const primary = '#1F4E79';

        // Animasi: satu kali saat muncul, singkat, hover di bawah 200ms, mati jika pengguna memilih reduced motion.
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        Chart.defaults.animation = reduceMotion ? false : { duration: 300, easing: 'easeOutQuad' };
        Chart.defaults.transitions.active.animation.duration = reduceMotion ? 0 : 150;
        Chart.defaults.maintainAspectRatio = false;
        Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
        Chart.defaults.color = '#6C757D';

        const shapEl = document.getElementById('shapChart');
        if (shapEl && shap.length) {
            const arahText = { negatif: 'Kepuasan rendah menaikkan keinginan keluar', positif: 'Nilai tinggi menaikkan keinginan keluar', campuran: 'Pengaruh bervariasi' };
            new Chart(shapEl, {
                type: 'bar',
                data: {
                    labels: shap.map(s => s.label),
                    datasets: [{ label: 'Besar pengaruh', data: shap.map(s => s.mean_abs_shap),
                        backgroundColor: shap.map(s => s.stable ? primary : '#9AA5B1'), borderRadius: 4, maxBarThickness: 22 }],
                },
                options: {
                    indexAxis: 'y',
                    plugins: { legend: { display: false }, tooltip: { callbacks: {
                        label: (ctx) => `Besar pengaruh: ${ctx.parsed.x.toFixed(3)}`,
                        afterLabel: (ctx) => {
                            const s = shap[ctx.dataIndex];
                            return [arahText[s.direction] ?? '', s.top3_frequency !== null ? `Masuk 3 besar pada ${Math.round(s.top3_frequency * 100)}% uji ulang` : ''];
                        } } } },
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#EEF0F2' }, title: { display: true, text: 'Besar pengaruh (rata-rata |SHAP|)' } },
                        y: { grid: { display: false } },
                    },
                },
            });
        }

        const factorEl = document.getElementById('factorChart');
        if (factorEl) {
            const codes = Object.keys(factorMeans).filter(c => factorMeans[c] !== null).sort((a, b) => factorMeans[a] - factorMeans[b]);
            const color = v => v < 3 ? '#DC3545' : (v < 3.5 ? '#FFC107' : '#198754');
            new Chart(factorEl, {
                type: 'bar',
                data: {
                    labels: codes.map(c => labels[c] ?? c),
                    datasets: [{ label: 'Skor rata-rata', data: codes.map(c => factorMeans[c]),
                        backgroundColor: codes.map(c => color(factorMeans[c])), borderRadius: 4, maxBarThickness: 22 }],
                },
                options: {
                    indexAxis: 'y',
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `Skor rata-rata: ${ctx.parsed.x.toFixed(2)} dari 5` } } },
                    scales: {
                        x: { min: 1, max: 5, ticks: { stepSize: 1 }, grid: { color: '#EEF0F2' } },
                        y: { grid: { display: false } },
                    },
                },
            });
        }
    })();
</script>
@endpush
@endif
