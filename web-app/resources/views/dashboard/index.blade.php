@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@if (! $d)
    <div class="card border-0 shadow-sm"><div class="card-body text-center p-5">
        <i class="bi bi-bar-chart fs-1 text-muted"></i>
        <h1 class="h5 mt-3">Belum ada data survei</h1>
        <p class="text-muted mb-0">Dashboard akan terisi setelah survei dibuka dan mahasiswa mulai mengisi.</p>
    </div></div>
@else
@php $run = $d['run']; $survey = $d['survey']; @endphp
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
    <h1 class="h4 mb-0">Dashboard Kepuasan &amp; Retensi</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.pdf', $survey) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        <a href="{{ route('reports.excel', $survey) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
    </div>
</div>

<form method="GET" class="card border-0 shadow-sm mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-md-5"><label class="form-label small mb-1">Survei</label>
        <select name="survey" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach ($surveys as $s)<option value="{{ $s->id }}" @selected($s->id === $survey->id)>{{ $s->title }} ({{ $s->statusLabel() }})</option>@endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label small mb-1">Angkatan</label>
        <select name="angkatan" class="form-select form-select-sm">
            <option value="">Semua</option>
            @foreach ($segments['angkatan'] as $v => $l)<option value="{{ $v }}" @selected(request('angkatan') === (string) $v)>{{ $l }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><label class="form-label small mb-1">Lama tinggal</label>
        <select name="lama_tinggal" class="form-select form-select-sm">
            <option value="">Semua</option>
            @foreach ($segments['lama_tinggal'] as $v => $l)<option value="{{ $v }}" @selected(request('lama_tinggal') === (string) $v)>{{ $l }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-sm btn-di w-100">Terapkan</button></div>
</div></form>

@include('partials.experimental-banner', ['run' => $run])

@if ($d['hidden'])
    <div class="alert alert-secondary"><i class="bi bi-shield-lock"></i> Segmen ini memiliki kurang dari {{ config('dorminsight.min_segment_size') }} responden. Statistik disembunyikan untuk melindungi anonimitas.</div>
@endif

<div class="row g-3 mb-3">
    @php
        $cards = [
            ['Responden valid', $d['n'].($d['filters'] ? ' / '.$d['total_valid'] : ''), 'bi-people'],
            ['Tingkat respons', round($d['response_rate'] * 100).'%', 'bi-graph-up'],
            ['Kepuasan rata-rata', $d['avg_kep_umum'] !== null ? number_format($d['avg_kep_umum'], 1).' / 5' : '-', 'bi-emoji-smile'],
            ['Menyatakan ingin keluar', $d['stated_exit_rate'] !== null ? round($d['stated_exit_rate'] * 100).'%' : '-', 'bi-door-open'],
        ];
    @endphp
    @foreach ($cards as [$label, $value, $icon])
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm stat-card h-100"><div class="card-body">
            <div class="small text-muted"><i class="bi {{ $icon }}"></i> {{ $label }}</div>
            <div class="value">{{ $value }}</div>
        </div></div></div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
        <h2 class="h6">Faktor paling berpengaruh terhadap keinginan keluar</h2>
        @if (! $run)
            <p class="text-muted small mb-0">Analisis ML belum dijalankan untuk survei ini.</p>
        @else
            <canvas id="shapChart" height="260"></canvas>
            <ul class="small mt-3 mb-2">@foreach ($d['insights'] as $line)<li>{{ $line }}</li>@endforeach</ul>
            <p class="small text-muted mb-0"><i class="bi bi-info-circle"></i> Panjang batang = besar pengaruh faktor terhadap perkiraan keinginan keluar (nilai SHAP).
                Persentase "uji ulang" menunjukkan seberapa sering faktor tersebut tetap masuk 3 besar saat model dilatih ulang dengan sampel berbeda. Faktor dominan dihitung dari seluruh responden.</p>
        @endif
    </div></div></div>
    <div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
        <h2 class="h6">Skor rata-rata per faktor (1 sampai 5)</h2>
        @if ($d['hidden'] || $d['n'] === 0)
            <p class="text-muted small mb-0">Tidak ada data untuk ditampilkan.</p>
        @else
            <canvas id="factorChart" height="260"></canvas>
        @endif
    </div></div></div>
</div>

@if ($run && ! $d['hidden'])
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <h2 class="h6">Distribusi kecenderungan keluar (perkiraan model)</h2>
    <div class="progress mb-2" style="height: 28px">
        @foreach (['rendah' => 'Rendah', 'sedang' => 'Sedang', 'tinggi' => 'Tinggi'] as $lvl => $label)
            @if ($d['risk_pct'][$lvl] > 0)
                <div class="progress-bar risk-{{ $lvl }} {{ $lvl === 'sedang' ? 'text-dark' : '' }}" style="width: {{ $d['risk_pct'][$lvl] * 100 }}%">{{ $label }} {{ round($d['risk_pct'][$lvl] * 100) }}%</div>
            @endif
        @endforeach
    </div>
    <p class="small text-muted mb-0">Rendah &lt; {{ ($run->metrics['risk_thresholds']['sedang'] ?? 0.4) * 100 }}%, Sedang sampai &lt; {{ ($run->metrics['risk_thresholds']['tinggi'] ?? 0.7) * 100 }}%, Tinggi di atasnya. Hanya ditampilkan secara agregat.</p>
</div></div>
@endif

<p class="small text-muted">
    @if ($run)
        Model <code>{{ $run->model_version }}</code> ({{ $run->algorithm }}) · F1 kelas ingin keluar: {{ $run->metrics['f1_keluar'] ?? '-' }}
        (baseline {{ $run->metrics['baseline_f1_keluar'] ?? '-' }}) · Analisis: {{ $run->run_at->format('d M Y H:i') }} ·
    @endif
    <i class="bi bi-info-circle"></i> Hasil bersifat agregat dan merupakan perkiraan model, bukan penilaian terhadap mahasiswa tertentu.
</p>
@endif
@endsection

@if ($d)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const shap = @json($d['shap']);
    const factorMeans = @json($d['factor_means']);
    const labels = @json($d['labels']);
    const primary = '#1F4E79';

    if (document.getElementById('shapChart') && shap.length) {
        new Chart(document.getElementById('shapChart'), {
            type: 'bar',
            data: {
                labels: shap.map(s => s.label),
                datasets: [{ label: 'Rata-rata |SHAP|', data: shap.map(s => s.mean_abs_shap),
                    backgroundColor: shap.map(s => s.stable ? primary : '#9AA5B1') }],
            },
            options: {
                indexAxis: 'y', plugins: { legend: { display: false }, tooltip: { callbacks: {
                    afterLabel: (ctx) => {
                        const s = shap[ctx.dataIndex];
                        const arah = { negatif: 'Kepuasan rendah menaikkan keinginan keluar', positif: 'Nilai tinggi menaikkan keinginan keluar', campuran: 'Pengaruh bervariasi' }[s.direction];
                        return [arah, s.top3_frequency !== null ? `Masuk 3 besar pada ${Math.round(s.top3_frequency * 100)}% uji ulang` : ''];
                    } } } },
                scales: { x: { beginAtZero: true, title: { display: true, text: 'Besar pengaruh' } } },
            },
        });
    }

    if (document.getElementById('factorChart')) {
        const codes = Object.keys(factorMeans).filter(c => factorMeans[c] !== null);
        new Chart(document.getElementById('factorChart'), {
            type: 'bar',
            data: {
                labels: codes.map(c => labels[c] ?? c),
                datasets: [{ label: 'Skor rata-rata', data: codes.map(c => factorMeans[c]),
                    backgroundColor: codes.map(c => factorMeans[c] < 3 ? '#DC3545' : (factorMeans[c] < 3.5 ? '#FFC107' : '#198754')) }],
            },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { min: 1, max: 5 } } },
        });
    }
</script>
@endpush
@endif
