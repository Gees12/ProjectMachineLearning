@extends('layouts.app')
@section('title', 'Beranda')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h4 mb-1">Halo, {{ auth()->user()->name }}</h1>
        <p class="text-muted">Suara Anda membantu pengelola memperbaiki Crystal Dormitory.</p>

        {{-- Status survei --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body p-4">
                @if (! $survey)
                    <div class="d-flex gap-3 align-items-center">
                        <i class="bi bi-calendar-x fs-1 text-muted"></i>
                        <div>
                            <h2 class="h5 mb-1">Belum ada survei aktif</h2>
                            <p class="text-muted mb-0">Survei berikutnya akan diumumkan oleh pengelola asrama.</p>
                        </div>
                    </div>
                @elseif ($submitted)
                    <div class="d-flex gap-3 align-items-center">
                        <i class="bi bi-check-circle-fill fs-1 text-success"></i>
                        <div>
                            <h2 class="h5 mb-1">Terima kasih, Anda sudah mengisi survei ini</h2>
                            <p class="text-muted mb-0">{{ $survey->title }}</p>
                        </div>
                    </div>
                @elseif (! $survey->isOpenForSubmission())
                    <div class="d-flex gap-3 align-items-center">
                        <i class="bi bi-hourglass-split fs-1 text-muted"></i>
                        <div>
                            <h2 class="h5 mb-1">{{ $survey->title }}</h2>
                            <p class="text-muted mb-0">
                                @if ($survey->open_at->isFuture())
                                    Dibuka {{ $survey->open_at->format('d M Y, H:i') }} WITA.
                                @else
                                    Periode pengisian sudah berakhir.
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">
                        <div class="d-flex gap-3 align-items-center">
                            <i class="bi bi-clipboard2-check fs-1 text-di"></i>
                            <div>
                                <span class="badge bg-success mb-1">Sedang dibuka</span>
                                <h2 class="h5 mb-1">{{ $survey->title }}</h2>
                                <p class="text-muted small mb-0"><i class="bi bi-clock"></i> Estimasi pengisian 5 sampai 8 menit · <i class="bi bi-lock-fill"></i> Anonim</p>
                            </div>
                        </div>
                        <a href="{{ route('survey.consent') }}" class="btn btn-di btn-lg">Isi Survei</a>
                    </div>
                    <hr>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-alarm text-danger"></i>
                        <span>Ditutup dalam <strong id="countdown" data-close="{{ $survey->close_at->toIso8601String() }}">{{ $remaining }}</strong></span>
                        <span class="text-muted small">({{ $survey->close_at->format('d M Y, H:i') }} WITA)</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Progres partisipasi asrama (agregat) --}}
        @if ($participation)
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3"><i class="bi bi-people"></i> Partisipasi penghuni</h2>
                    <div class="d-flex justify-content-between align-items-end mb-1">
                        <span><strong class="fs-4 text-di">{{ $participation['count'] }}</strong> <span class="text-muted">dari {{ $participation['population'] }} penghuni sudah mengisi</span></span>
                        <strong>{{ $participation['percent'] }}%</strong>
                    </div>
                    @php $targetPct = min(100, round($participation['target'] / $participation['population'] * 100)); @endphp
                    <div class="position-relative">
                        <div class="progress" style="height: 12px" role="progressbar" aria-valuenow="{{ $participation['percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Partisipasi penghuni">
                            <div class="progress-bar {{ $participation['count'] >= $participation['target'] ? 'bg-success' : 'bg-warning' }}" style="width: {{ $participation['percent'] }}%"></div>
                        </div>
                        <div class="position-absolute top-0 border-start border-2 border-dark" style="left: {{ $targetPct }}%; height: 12px" title="Target minimum"></div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        @if ($participation['count'] >= $participation['target'])
                            <i class="bi bi-check-circle text-success"></i> Target minimum {{ $participation['target'] }} penghuni sudah tercapai. Semakin banyak yang mengisi, semakin akurat hasilnya.
                        @else
                            Dibutuhkan setidaknya <strong>{{ $participation['target'] }} penghuni</strong> agar hasil analisis dapat dipercaya. Masih kurang {{ $participation['target'] - $participation['count'] }} lagi.
                        @endif
                    </p>
                </div>
            </div>
        @endif

        {{-- Riwayat partisipasi pribadi (tanpa isi jawaban) --}}
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h6 mb-3"><i class="bi bi-clock-history"></i> Riwayat partisipasi Anda</h2>
                @if ($history->isEmpty())
                    <p class="text-muted mb-0">Anda belum pernah mengisi survei.</p>
                @else
                    <p class="mb-2">Anda sudah berpartisipasi di <strong>{{ $history->count() }}</strong> periode survei. Terima kasih!</p>
                    <ul class="list-group list-group-flush">
                        @foreach ($history as $item)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>{{ $item->survey?->title ?? 'Survei' }}</span>
                                <span class="text-muted small text-nowrap"><i class="bi bi-check2"></i> {{ $item->submitted_date->format('d M Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <p class="small text-muted mt-3 mb-0"><i class="bi bi-lock-fill"></i> Yang tercatat hanya bahwa Anda sudah mengisi. Isi jawaban Anda tidak tersimpan bersama akun ini, sehingga tidak dapat dilihat siapa pun, termasuk Anda sendiri.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const el = document.getElementById('countdown');
    if (!el) return;
    const close = new Date(el.dataset.close).getTime();
    const render = () => {
        let s = Math.floor((close - Date.now()) / 1000);
        if (s <= 0) { el.textContent = 'beberapa saat (segarkan halaman)'; return; }
        const d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
        el.textContent = d > 0 ? `${d} hari${h ? ` ${h} jam` : ''}` : h > 0 ? `${h} jam${m ? ` ${m} menit` : ''}` : `${Math.max(1, m)} menit`;
    };
    render();
    setInterval(render, 30000);
})();
</script>
@endpush
