@extends('layouts.app')
@section('title', 'Terima Kasih')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 text-center">
        <div class="card shadow-sm border-0"><div class="card-body p-5">
            <i class="bi bi-check-circle-fill fs-1 text-success"></i>
            <h1 class="h4 mt-3">Survei terkirim. Terima kasih!</h1>
            <p class="text-muted">Masukan Anda membantu pengelola memperbaiki Crystal Dormitory. Jawaban Anda tersimpan secara anonim.</p>
            <a href="{{ route('survey.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div></div>
    </div>
</div>
@endsection
