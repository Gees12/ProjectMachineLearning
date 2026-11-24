@extends('layouts.app')
@section('title', 'Survei')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Survei &amp; Data</h1>
    <a href="{{ route('admin.surveys.create') }}" class="btn btn-di"><i class="bi bi-plus-lg"></i> Survei baru</a>
</div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead class="table-light"><tr><th>Judul</th><th>Periode</th><th>Status</th><th>Pertanyaan</th><th>Respons</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
    @forelse ($surveys as $s)
        <tr>
            <td><a href="{{ route('admin.surveys.show', $s) }}">{{ $s->title }}</a></td>
            <td class="small">{{ $s->open_at->format('d M Y') }} - {{ $s->close_at->format('d M Y') }}</td>
            <td><span class="badge bg-{{ ['draft' => 'secondary', 'active' => 'success', 'closed' => 'dark'][$s->status] }}">{{ $s->statusLabel() }}</span></td>
            <td>{{ $s->questions_count }}</td>
            <td>{{ $s->responses_count }}</td>
            <td class="text-end text-nowrap">
                <a href="{{ route('admin.responses.index', $s) }}" class="btn btn-sm btn-outline-secondary">Respons</a>
                <a href="{{ route('admin.analyses.index', $s) }}" class="btn btn-sm btn-outline-primary">Analisis</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada survei. Jalankan seeder atau buat survei baru.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>
@endsection
