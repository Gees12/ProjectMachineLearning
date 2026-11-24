@extends('layouts.app')
@section('title', 'Simulasi Skenario')
@section('content')
<h1 class="h4">Simulasi Skenario Perbaikan</h1>
<p class="text-muted">Geser nilai faktor untuk melihat perkiraan perubahan kecenderungan keluar mahasiswa "rata-rata".</p>
@if (! $baseline)
    <div class="alert alert-secondary">Belum ada survei tertutup dengan respons valid. Simulasi membutuhkan data dan model ML.</div>
@else
<div class="row g-3">
    <div class="col-lg-7"><div class="card border-0 shadow-sm"><div class="card-body">
        <p class="small text-muted">Nilai awal = rata-rata <strong>{{ $survey->title }}</strong>.</p>
        <form method="POST" action="{{ route('simulation.run') }}">
            @csrf
            @foreach ($factorCodes as $code)
                @php $base = $baseline[$code] ?? 3; $val = old("scenario.{$code}", $scenario[$code] ?? $base); @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between small">
                        <label for="s_{{ $code }}">{{ $labels[$code] ?? $code }}</label>
                        <span>awal {{ number_format($base, 2) }} → <strong id="v_{{ $code }}">{{ number_format($val, 2) }}</strong></span>
                    </div>
                    <input type="range" class="form-range" min="1" max="5" step="0.1" id="s_{{ $code }}" name="scenario[{{ $code }}]" value="{{ $val }}"
                           oninput="document.getElementById('v_{{ $code }}').textContent = Number(this.value).toFixed(2)">
                </div>
            @endforeach
            <button class="btn btn-di"><i class="bi bi-calculator"></i> Hitung</button>
            <a href="{{ route('simulation.index') }}" class="btn btn-outline-secondary">Reset</a>
        </form>
    </div></div></div>
    <div class="col-lg-5"><div class="card border-0 shadow-sm"><div class="card-body">
        <h2 class="h6">Hasil</h2>
        @if ($result)
            @php $delta = round($result['delta'] * 100, 1); @endphp
            <div class="d-flex align-items-center gap-3 my-3">
                <div class="text-center"><div class="small text-muted">Sebelum</div><div class="fs-3 fw-semibold">{{ round($result['baseline_probability'] * 100) }}%</div></div>
                <i class="bi bi-arrow-right fs-3"></i>
                <div class="text-center"><div class="small text-muted">Sesudah</div><div class="fs-3 fw-semibold text-di">{{ round($result['scenario_probability'] * 100) }}%</div></div>
            </div>
            <p>{{ $delta < 0 ? 'Turun' : ($delta > 0 ? 'Naik' : 'Tidak berubah') }} {{ abs($delta) }} poin persentase.</p>
            <div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle"></i> {{ $result['note'] ?? 'Hasil simulasi adalah perkiraan model, bukan jaminan.' }} Hubungan yang ditemukan model bersifat asosiasi, bukan sebab-akibat.</div>
        @else
            <p class="text-muted small mb-0">Ubah nilai faktor lalu tekan Hitung.</p>
        @endif
    </div></div></div>
</div>
@endif
@endsection
