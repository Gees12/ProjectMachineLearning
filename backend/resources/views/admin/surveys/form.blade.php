@extends('layouts.app')
@section('title', $survey->exists ? 'Ubah Survei' : 'Survei Baru')
@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h1 class="h5 mb-3">{{ $survey->exists ? 'Ubah Survei' : 'Survei Baru' }}</h1>
    <form method="POST" action="{{ $survey->exists ? route('admin.surveys.update', $survey) : route('admin.surveys.store') }}">
        @csrf @if($survey->exists) @method('PUT') @endif
        <div class="mb-3"><label class="form-label">Judul</label><input name="title" class="form-control" value="{{ old('title', $survey->title) }}" placeholder="Survei Kepuasan Semester II 2026/2027" required></div>
        <div class="mb-3"><label class="form-label">Deskripsi / pengantar</label><textarea name="description" rows="4" class="form-control">{{ old('description', $survey->description) }}</textarea></div>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Dibuka</label><input type="datetime-local" name="open_at" class="form-control" value="{{ old('open_at', $survey->open_at?->format('Y-m-d\TH:i')) }}" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Ditutup</label><input type="datetime-local" name="close_at" class="form-control" value="{{ old('close_at', $survey->close_at?->format('Y-m-d\TH:i')) }}" required></div>
        </div>
        <a href="{{ $survey->exists ? route('admin.surveys.show', $survey) : route('admin.surveys.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button class="btn btn-di">Simpan</button>
    </form>
</div></div>
</div></div>
@endsection
