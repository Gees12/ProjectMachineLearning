@extends('layouts.app')
@section('title', 'Analisis ML')
@section('content')
<a href="{{ route('admin.surveys.show', $survey) }}" class="small">&larr; {{ $survey->title }}</a>
<h1 class="h4 mb-3">Analisis Machine Learning</h1>
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <p class="mb-2">Respons valid: <strong>{{ $validCount }}</strong> (target minimum {{ config('dorminsight.min_valid_responses') }}).</p>
    @if ($survey->status !== 'closed')
        <div class="alert alert-secondary mb-0">Tutup survei terlebih dahulu untuk menjalankan analisis.</div>
    @else
        @if ($validCount < config('dorminsight.min_valid_responses'))
            <div class="alert alert-warning">Jumlah respons valid belum mencapai target. Hasil akan bersifat indikatif.</div>
        @endif
        <form method="POST" action="{{ route('admin.analyses.run', $survey) }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerText = 'Menjalankan...';">
            @csrf
            <button class="btn btn-di"><i class="bi bi-cpu"></i> Jalankan Analisis</button>
            <span class="small text-muted ms-2">Panggilan pertama bisa lambat jika ML service di hosting gratis sedang "tidur".</span>
        </form>
    @endif
</div></div>

<div class="card border-0 shadow-sm"><div class="table-responsive">
<table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th>Waktu</th><th>Oleh</th><th>Status</th><th>Model</th><th>Data</th><th>Respons</th><th>F1 keluar</th><th>Keterangan</th></tr></thead>
    <tbody>
    @forelse ($runs as $run)
        <tr>
            <td class="small">{{ $run->run_at->format('d M Y H:i') }}</td>
            <td class="small">{{ $run->runner?->name ?? '-' }}</td>
            <td>@if($run->status === 'success')<span class="badge bg-success">Sukses</span>@else<span class="badge bg-danger">Gagal</span>@endif</td>
            <td><code>{{ $run->model_version }}</code></td>
            <td class="small">{{ $run->metrics['data_source'] ?? '-' }}</td>
            <td>{{ $run->n_responses }}</td>
            <td>{{ $run->metrics['f1_keluar'] ?? '-' }}</td>
            <td class="small text-danger">{{ $run->error_message }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada analisis.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>
@endsection
