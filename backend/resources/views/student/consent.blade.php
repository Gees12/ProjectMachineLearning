@extends('layouts.app')
@section('title', 'Persetujuan')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4">{{ $survey->title }}</h1>
                @if ($survey->description)
                    <p>{!! nl2br(e($survey->description)) !!}</p>
                @endif
                <div class="alert alert-info">
                    <p class="mb-2"><i class="bi bi-lock-fill"></i> <strong>Jawaban Anda anonim.</strong></p>
                    <p class="mb-0 small">Survei ini bertujuan memahami pengalaman penghuni Crystal Dormitory untuk perbaikan layanan asrama.
                        Partisipasi bersifat sukarela. Jawaban Anda <strong>tidak terhubung</strong> dengan akun Anda; pengelola asrama hanya
                        melihat hasil gabungan dan tidak dapat mengetahui jawaban perorangan. Anda boleh berhenti kapan saja sebelum mengirim,
                        dan jawaban yang belum dikirim tidak disimpan. Data hanya digunakan untuk proyek DormInsight.</p>
                </div>
                <p class="small text-muted"><i class="bi bi-clock"></i> Estimasi pengisian 5 sampai 8 menit.</p>
                <form method="POST" action="{{ route('survey.start') }}">
                    @csrf
                    <div class="form-check mb-3">
                        <input class="form-check-input @error('agree') is-invalid @enderror" type="checkbox" name="agree" value="1" id="agree" required>
                        <label class="form-check-label" for="agree">Saya memahami dan bersedia mengisi survei ini.</label>
                        @error('agree')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <a href="{{ route('survey.index') }}" class="btn btn-outline-secondary">Kembali</a>
                    <button class="btn btn-di">Mulai Survei</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
