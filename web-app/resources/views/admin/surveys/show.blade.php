@extends('layouts.app')
@section('title', $survey->title)
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <a href="{{ route('admin.surveys.index') }}" class="small">&larr; Semua survei</a>
        <h1 class="h4 mb-1">{{ $survey->title }}</h1>
        <div class="small text-muted">{{ $survey->open_at->format('d M Y H:i') }} - {{ $survey->close_at->format('d M Y H:i') }} ·
            <span class="badge bg-{{ ['draft' => 'secondary', 'active' => 'success', 'closed' => 'dark'][$survey->status] }}">{{ $survey->statusLabel() }}</span>
            @if ($locked) · <i class="bi bi-lock-fill"></i> Pertanyaan terkunci karena sudah ada respons @endif
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.surveys.edit', $survey) }}" class="btn btn-sm btn-outline-secondary">Ubah info</a>
        <form method="POST" action="{{ route('admin.surveys.duplicate', $survey) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Salin</button></form>
        @if ($survey->status !== 'active')
            <form method="POST" action="{{ route('admin.surveys.open', $survey) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Buka survei</button></form>
        @else
            <form method="POST" action="{{ route('admin.surveys.close', $survey) }}" onsubmit="return confirm('Tutup survei? Mahasiswa tidak dapat mengisi lagi.')">@csrf @method('PATCH')<button class="btn btn-sm btn-dark">Tutup survei</button></form>
        @endif
        <a href="{{ route('admin.responses.index', $survey) }}" class="btn btn-sm btn-outline-primary">Respons</a>
        <a href="{{ route('admin.analyses.index', $survey) }}" class="btn btn-sm btn-di">Analisis</a>
        @unless ($locked)
            <form method="POST" action="{{ route('admin.surveys.destroy', $survey) }}" onsubmit="return confirm('Hapus survei ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>
        @endunless
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h6">Faktor ({{ $survey->factors->count() }})</h2>
            <ul class="list-group list-group-flush mb-3">
                @foreach ($survey->factors as $f)
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span><code>{{ $f->code }}</code> {{ $f->name }}</span>
                        @unless ($locked)
                            <form method="POST" action="{{ route('admin.factors.destroy', [$survey, $f]) }}">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0" title="Hapus"><i class="bi bi-trash"></i></button></form>
                        @endunless
                    </li>
                @endforeach
            </ul>
            @unless ($locked)
                <form method="POST" action="{{ route('admin.factors.store', $survey) }}" class="row g-2">
                    @csrf
                    <div class="col-4"><input name="code" class="form-control form-control-sm" placeholder="F_XXX" required></div>
                    <div class="col-8"><input name="name" class="form-control form-control-sm" placeholder="Nama faktor" required></div>
                    <div class="col-8"><input name="description" class="form-control form-control-sm" placeholder="Deskripsi (opsional)"></div>
                    <div class="col-4"><input name="sort_order" type="number" min="0" class="form-control form-control-sm" value="{{ $survey->factors->count() + 1 }}"></div>
                    <div class="col-12"><button class="btn btn-sm btn-outline-primary">Tambah faktor</button></div>
                </form>
            @endunless
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Pertanyaan ({{ $survey->questions->count() }})</h2>
                @unless ($locked)<a href="{{ route('admin.surveys.questions.create', $survey) }}" class="btn btn-sm btn-di"><i class="bi bi-plus-lg"></i> Pertanyaan</a>@endunless
            </div>
            <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>#</th><th>Kode</th><th>Pertanyaan</th><th>Tipe</th><th>Peran ML</th><th></th></tr></thead>
                <tbody>
                @foreach ($survey->questions as $q)
                    <tr @if($q->ml_role === 'target') class="table-warning" @endif>
                        <td>{{ $q->sort_order }}</td>
                        <td><code>{{ $q->code }}</code></td>
                        <td class="small">{{ $q->text }} @if($q->factor)<br><span class="text-muted">Faktor: {{ $q->factor->name }}</span>@endif</td>
                        <td class="small">{{ \App\Models\Question::TYPES[$q->type] }}</td>
                        <td class="small">{{ $q->ml_role }}</td>
                        <td class="text-nowrap">
                            @unless ($locked)
                                <a href="{{ route('admin.surveys.questions.edit', [$survey, $q]) }}" class="btn btn-link btn-sm p-0"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.surveys.questions.destroy', [$survey, $q]) }}" class="d-inline" onsubmit="return confirm('Hapus pertanyaan?')">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0"><i class="bi bi-trash"></i></button></form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            <p class="small text-muted mb-0">Baris kuning = pertanyaan label target. Survei hanya dapat dibuka jika ada tepat satu.</p>
        </div></div>
    </div>
</div>
@endsection
