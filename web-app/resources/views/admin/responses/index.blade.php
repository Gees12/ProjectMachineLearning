@extends('layouts.app')
@section('title', 'Data Respons')
@section('content')
<a href="{{ route('admin.surveys.show', $survey) }}" class="small">&larr; {{ $survey->title }}</a>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Data Respons</h1>
    <a href="{{ route('admin.responses.export', $survey) }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Ekspor CSV anonim</a>
</div>
@php $pct = min(100, round($stats['valid'] / max(1, $stats['population']) * 100)); @endphp
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="d-flex flex-wrap gap-4 mb-2">
        <div><span class="text-muted small">Respons masuk</span><div class="fs-4 fw-semibold">{{ $stats['total'] }}</div></div>
        <div><span class="text-muted small">Valid</span><div class="fs-4 fw-semibold text-success">{{ $stats['valid'] }}</div></div>
        <div><span class="text-muted small">Target minimum</span><div class="fs-4 fw-semibold">{{ $stats['target'] }}</div></div>
        <div><span class="text-muted small">Penghuni</span><div class="fs-4 fw-semibold">{{ $stats['population'] }}</div></div>
        <div><span class="text-muted small">Akun mahasiswa aktif</span><div class="fs-4 fw-semibold">{{ $stats['students'] }}</div></div>
    </div>
    <div class="progress" style="height: 10px"><div class="progress-bar {{ $stats['valid'] >= $stats['target'] ? 'bg-success' : 'bg-warning' }}" style="width: {{ $pct }}%"></div></div>
    <div class="small text-muted mt-1">Tingkat respons valid {{ $pct }}% dari penghuni. @if($stats['valid'] < $stats['target']) Belum mencapai target minimum untuk analisis ML. @endif</div>
</div></div>

<div class="card border-0 shadow-sm"><div class="table-responsive">
<table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th>ID</th><th>Tanggal</th><th>Durasi</th><th>Status</th><th>Ingin keluar</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
    @forelse ($responses as $r)
        <tr>
            <td><code>{{ \Illuminate\Support\Str::substr($r->id, 0, 8) }}…</code></td>
            <td>{{ $r->submitted_date->format('d M Y') }}</td>
            <td>{{ intdiv($r->duration_seconds, 60) }}m {{ $r->duration_seconds % 60 }}s</td>
            <td>
                @if ($r->is_valid)<span class="badge bg-success">Valid</span>@else<span class="badge bg-warning text-dark">Dikecualikan</span>@endif
                @if ($r->flag_reason)<span class="small text-muted">{{ \App\Services\ResponseValidator::reasonLabel($r->flag_reason) }}</span>@endif
            </td>
            <td>{{ $r->feature?->target ? 'Ya' : 'Tidak' }}</td>
            <td class="text-end">
                <form method="POST" action="{{ route('admin.responses.validity', $r) }}">@csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-{{ $r->is_valid ? 'warning' : 'success' }}">{{ $r->is_valid ? 'Kecualikan' : 'Validkan' }}</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada respons.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>
<div class="mt-3">{{ $responses->links('pagination::bootstrap-5') }}</div>
<p class="small text-muted mt-2"><i class="bi bi-lock-fill"></i> Respons tidak terhubung dengan akun mahasiswa. Tanggal disimpan tanpa jam.</p>
@endsection
