<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan DormInsight</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 18px; color: #1F4E79; margin: 0 0 4px; }
    h2 { font-size: 13px; color: #1F4E79; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-top: 18px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 4px 6px; border-bottom: 1px solid #e5e5e5; text-align: left; vertical-align: top; }
    th { background: #f2f4f7; }
    .bar { background: #1F4E79; height: 9px; }
    .muted { color: #666; }
    .warn { background: #fff3cd; border: 1px solid #ffe08a; padding: 6px 8px; margin: 8px 0; }
</style>
</head>
<body>
@php $run = $d['run']; $survey = $d['survey']; $maxShap = collect($d['shap'])->max('mean_abs_shap') ?: 1; @endphp
<h1>Laporan Analisis Kepuasan &amp; Retensi</h1>
<div>Crystal Dormitory, Universitas Klabat</div>
<div class="muted">{{ $survey->title }} · {{ $survey->open_at->format('d M Y') }} - {{ $survey->close_at->format('d M Y') }} · Dicetak {{ now()->format('d M Y H:i') }}</div>

@if ($run && $run->isExperimental())
    <div class="warn"><strong>Hasil eksperimental:</strong> model dilatih dengan data dummy atau data yang belum memadai. Belum boleh dijadikan dasar keputusan.</div>
@endif

<h2>Ringkasan</h2>
<table>
    <tr><th>Responden valid</th><td>{{ $d['total_valid'] }} dari {{ config('dorminsight.population') }} penghuni ({{ round($d['response_rate'] * 100, 1) }}%)</td></tr>
    <tr><th>Kepuasan keseluruhan rata-rata</th><td>{{ $d['avg_kep_umum'] ?? '-' }} / 5</td></tr>
    <tr><th>Menyatakan ingin keluar</th><td>{{ $d['stated_exit_rate'] !== null ? round($d['stated_exit_rate'] * 100, 1).'%' : '-' }}</td></tr>
    <tr><th>Tanggal analisis</th><td>{{ $run?->run_at?->format('d M Y H:i') ?? 'Belum dianalisis' }}</td></tr>
    <tr><th>Model</th><td>{{ $run ? $run->model_version.' ('.$run->algorithm.'), F1 kelas ingin keluar '.($run->metrics['f1_keluar'] ?? '-') : '-' }}</td></tr>
</table>

<h2>Skor Rata-rata per Faktor (1 sampai 5)</h2>
<table>
    <tr><th style="width: 40%">Faktor</th><th style="width: 12%">Skor</th><th></th></tr>
    @foreach ($d['factor_means'] as $code => $mean)
        <tr><td>{{ $d['labels'][$code] ?? $code }}</td><td>{{ $mean ?? '-' }}</td>
            <td>@if($mean)<div class="bar" style="width: {{ ($mean - 1) / 4 * 100 }}%"></div>@endif</td></tr>
    @endforeach
</table>

@if ($run)
<h2>Faktor Paling Berpengaruh terhadap Keinginan Keluar</h2>
<table>
    <tr><th>#</th><th>Faktor</th><th>Pengaruh</th><th>Arah</th><th>Konsistensi</th></tr>
    @foreach ($d['shap'] as $s)
        <tr><td>{{ $s['rank'] }}</td><td>{{ $s['label'] }}</td>
            <td><div class="bar" style="width: {{ $s['mean_abs_shap'] / $maxShap * 100 }}px"></div></td>
            <td>{{ $s['direction'] }}</td>
            <td>{{ $s['top3_frequency'] !== null ? round($s['top3_frequency'] * 100).'%' : '-' }}</td></tr>
    @endforeach
</table>
<ul>@foreach ($d['insights'] as $line)<li>{{ $line }}</li>@endforeach</ul>

<h2>Distribusi Kecenderungan Keluar (Perkiraan Model)</h2>
<table>
    @foreach ($d['risk'] as $lvl => $count)
        <tr><td>{{ ucfirst($lvl) }}</td><td>{{ $count }} responden</td><td>{{ round($d['risk_pct'][$lvl] * 100, 1) }}%</td></tr>
    @endforeach
</table>
@endif

<p class="muted" style="margin-top: 20px">Catatan: seluruh angka bersifat agregat dan anonim. Hasil model menunjukkan hubungan (asosiasi), bukan sebab-akibat,
    dan tidak boleh dipakai untuk menilai mahasiswa tertentu. "Konsistensi" = persentase uji ulang (bootstrap) di mana faktor tetap masuk 3 besar.</p>
</body>
</html>
