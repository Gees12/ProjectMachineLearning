@extends('layouts.app')
@section('title', 'Survei')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 text-center">
                @if (! $survey)
                    <i class="bi bi-calendar-x fs-1 text-muted"></i>
                    <h1 class="h4 mt-3">Belum ada survei aktif</h1>
                    <p class="text-muted">Survei akan diumumkan oleh pengelola asrama.</p>
                @elseif ($submitted)
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <h1 class="h4 mt-3">Terima kasih!</h1>
                    <p class="text-muted">Anda sudah mengisi <strong>{{ $survey->title }}</strong>.</p>
                @elseif (! $survey->isOpenForSubmission())
                    <i class="bi bi-hourglass-split fs-1 text-muted"></i>
                    <h1 class="h4 mt-3">{{ $survey->title }}</h1>
                    <p class="text-muted">Survei dibuka {{ $survey->open_at->format('d M Y H:i') }} sampai {{ $survey->close_at->format('d M Y H:i') }}.</p>
                @else
                    <i class="bi bi-clipboard2-check fs-1 text-di"></i>
                    <h1 class="h4 mt-3">{{ $survey->title }}</h1>
                    <p class="text-muted">Dibuka sampai {{ $survey->close_at->format('d M Y H:i') }}. Estimasi pengisian 5 sampai 8 menit.</p>
                    <a href="{{ route('survey.consent') }}" class="btn btn-di btn-lg">Isi Survei</a>
                    <p class="small text-muted mt-3"><i class="bi bi-lock-fill"></i> Jawaban Anda anonim.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
