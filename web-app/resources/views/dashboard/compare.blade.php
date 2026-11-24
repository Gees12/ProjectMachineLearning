@extends('layouts.app')
@section('title', 'Perbandingan Periode')
@section('content')
<div class="mb-3">
    <h1 class="h4 mb-1">Perbandingan Antarperiode</h1>
    <p class="small text-muted mb-0">Lihat apakah skor tiap faktor membaik setelah ada perbaikan fasilitas atau layanan.</p>
</div>

<form method="GET" class="card border-0 shadow-sm mb-3"><div class="card-body row g-2 align-items-end">
    @foreach (['a' => 'Periode A (sebelum)', 'b' => 'Periode B (sesudah)'] as $key => $label)
        <div class="col-md-5"><label for="f-{{ $key }}" class="form-label small mb-1">{{ $label }}</label>
            <select id="f-{{ $key }}" name="{{ $key }}" class="form-select form-select-sm">
                <option value="">Pilih survei</option>
                @foreach ($surveys as $s)<option value="{{ $s->id }}" @selected((string) request($key) === (string) $s->id)>{{ $s->title }}</option>@endforeach
            </select>
        </div>
    @endforeach
    <div class="col-md-2"><button class="btn btn-sm btn-di w-100">Bandingkan</button></div>
</div></form>

@if ($da && $db)
<div class="row g-3 mb-3">
    @foreach ([['A', $a, $da], ['B', $b, $db]] as [$tag, $s, $dx])
        <div class="col-md-6"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted">Periode {{ $tag }}</div>
            <div class="fw-semibold mb-2 text-truncate" title="{{ $s->title }}">{{ $s->title }}</div>
            <div class="row g-2 small tabular-nums">
                <div class="col-4"><div class="text-muted">Responden</div><div class="fs-5 fw-semibold text-di">{{ $dx['total_valid'] }}</div></div>
                <div class="col-4"><div class="text-muted">Kepuasan</div><div class="fs-5 fw-semibold text-di">{{ $dx['avg_kep_umum'] !== null ? number_format($dx['avg_kep_umum'], 1, ',', '.') : '-' }}</div></div>
                <div class="col-4"><div class="text-muted">Ingin keluar</div><div class="fs-5 fw-semibold text-di">{{ $dx['stated_exit_rate'] !== null ? round($dx['stated_exit_rate'] * 100).'%' : '-' }}</div></div>
            </div>
        </div></div></div>
    @endforeach
</div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
<table class="table align-middle mb-0 tabular-nums">
    <thead class="table-light"><tr><th scope="col">Faktor</th><th scope="col" class="text-end">A</th><th scope="col" class="text-end">B</th><th scope="col" class="text-end">Perubahan</th></tr></thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $r['label'] }}</td>
            <td class="text-end">{{ $r['a'] ?? '-' }}</td>
            <td class="text-end">{{ $r['b'] ?? '-' }}</td>
            <td class="text-end">
                @if ($r['delta'] === null) -
                @elseif ($r['delta'] > 0)<span class="text-success"><i class="bi bi-arrow-up-short" aria-hidden="true"></i><span class="visually-hidden">Naik </span>{{ $r['delta'] }}</span>
                @elseif ($r['delta'] < 0)<span class="text-danger"><i class="bi bi-arrow-down-short" aria-hidden="true"></i><span class="visually-hidden">Turun </span>{{ abs($r['delta']) }}</span>
                @else <span class="text-muted">0</span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div></div>
<p class="small text-muted mt-2">Perbedaan kecil (misalnya di bawah 0,2) dapat terjadi karena variasi responden, bukan perubahan nyata.</p>
@else
    <div class="card border-0 shadow-sm"><div class="card-body text-center p-5">
        <i class="bi bi-arrow-left-right fs-1 text-muted" aria-hidden="true"></i>
        <h2 class="h6 mt-3">{{ request('a') || request('b') ? 'Pilih dua survei untuk dibandingkan' : 'Belum ada periode yang dipilih' }}</h2>
        <p class="small text-muted mb-0">Pilih Periode A dan Periode B pada form di atas, lalu klik <strong>Bandingkan</strong>.</p>
    </div></div>
@endif
@endsection
