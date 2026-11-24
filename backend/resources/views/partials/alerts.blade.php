@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $class)
    @if (session($key))
        <div class="alert alert-{{ $class }} alert-dismissible fade show" role="alert">
            {{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
@endforeach
@if (session('import_errors'))
    <div class="alert alert-warning">
        <strong>Baris yang gagal diimpor:</strong>
        <ul class="mb-0">@foreach (session('import_errors') as $err)<li>{{ $err }}</li>@endforeach</ul>
    </div>
@endif
@if ($errors->any() && ! $errors->has('login'))
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
    </div>
@endif
