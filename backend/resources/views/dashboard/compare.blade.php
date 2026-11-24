@extends('layouts.app')
@section('title', 'Perbandingan Periode')
@section('content')
<h1 class="h4 mb-3">Perbandingan Antarperiode</h1>
<form method="GET" class="card border-0 shadow-sm mb-3"><div class="card-body row g-2 align-items-end">
    @foreach (['a' => 'Periode A (sebelum)', 'b' => 'Periode B (sesudah)'] as $key => $label)
        <div class="col-md-5"><label class="form-label small mb-1">{{ $label }}</label>
            <select name="{{ $key }}" class="form-select form-select-sm">
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
        <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="small text-muted">Periode {{ $tag }}</div>
            <div class="fw-semibold">{{ $s->title }}</div>
            <div class="small">Responden valid: {{ $dx['total_valid'] }} · Kepuasan rata-rata: {{ $dx['avg_kep_umum'] ?? '-' }} ·
                Ingin keluar: {{ $dx['stated_exit_rate'] !== null ? round($dx['stated_exit_rate'] * 100).'%' : '-' }}</div>
        </div></div></div>
    @endforeach
</div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead class="table-light"><tr><th>Faktor</th><th class="text-end">A</th><th class="text-end">B</th><th class="text-end">Perubahan</th></tr></thead>
    <tbody>
    @foreach ($rows as $r)
        <tr>
            <td>{{ $r['label'] }}</td>
            <td class="text-end">{{ $r['a'] ?? '-' }}</td>
            <td class="text-end">{{ $r['b'] ?? '-' }}</td>
            <td class="text-end">
                @if ($r['delta'] === null) - @elseif ($r['delta'] > 0)<span class="text-success">▲ {{ $r['delta'] }}</span>@elseif ($r['delta'] < 0)<span class="text-danger">▼ {{ abs($r['delta']) }}</span>@else 0 @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div></div>
<p class="small text-muted mt-2">Perbedaan kecil (misalnya di bawah 0,2) dapat terjadi karena variasi responden, bukan perubahan nyata.</p>
@elseif (request('a') || request('b'))
    <div class="alert alert-secondary">Pilih dua survei untuk dibandingkan.</div>
@endif
@endsection
