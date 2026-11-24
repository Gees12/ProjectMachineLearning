@extends('layouts.app')
@section('title', 'Pertanyaan')
@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h1 class="h5 mb-3">{{ $question->exists ? 'Ubah' : 'Tambah' }} Pertanyaan · <span class="text-muted">{{ $survey->title }}</span></h1>
    <form method="POST" action="{{ $question->exists ? route('admin.surveys.questions.update', [$survey, $question]) : route('admin.surveys.questions.store', $survey) }}">
        @csrf @if($question->exists) @method('PUT') @endif
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Kode</label><input name="code" class="form-control" value="{{ old('code', $question->code) }}" placeholder="F_MAK_1" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Urutan</label><input name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $question->sort_order) }}" required></div>
            <div class="col-md-4 mb-3 d-flex align-items-end"><div class="form-check">
                <input type="hidden" name="is_required" value="0">
                <input class="form-check-input" type="checkbox" name="is_required" value="1" id="req" @checked(old('is_required', $question->is_required))>
                <label class="form-check-label" for="req">Wajib dijawab</label>
            </div></div>
        </div>
        <div class="mb-3"><label class="form-label">Teks pertanyaan</label><textarea name="text" rows="2" class="form-control" required>{{ old('text', $question->text) }}</textarea></div>
        <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Tipe</label>
                <select name="type" class="form-select">@foreach (\App\Models\Question::TYPES as $k => $label)<option value="{{ $k }}" @selected(old('type', $question->type) === $k)>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label">Peran dalam ML</label>
                <select name="ml_role" class="form-select">@foreach (\App\Models\Question::ML_ROLES as $k => $label)<option value="{{ $k }}" @selected(old('ml_role', $question->ml_role) === $k)>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label">Faktor</label>
                <select name="factor_id" class="form-select">
                    <option value="">(tanpa faktor)</option>
                    @foreach ($survey->factors as $f)<option value="{{ $f->id }}" @selected((string) old('factor_id', $question->factor_id) === (string) $f->id)>{{ $f->code }} · {{ $f->name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="mb-3"><label class="form-label">Opsi (khusus pilihan tunggal)</label>
            <textarea name="options" rows="4" class="form-control font-monospace" placeholder="nilai|label per baris, mis.&#10;lt1|Kurang dari 1 tahun&#10;1_2|1 sampai 2 tahun">{{ old('options', $optionsText) }}</textarea>
            <div class="form-text">Nilai opsi untuk ANGKATAN dan LAMA_TINGGAL harus sama dengan yang dikenal model ML (lihat Data Dictionary B.3).</div>
        </div>
        <a href="{{ route('admin.surveys.show', $survey) }}" class="btn btn-outline-secondary">Batal</a>
        <button class="btn btn-di">Simpan</button>
    </form>
</div></div>
</div></div>
@endsection
