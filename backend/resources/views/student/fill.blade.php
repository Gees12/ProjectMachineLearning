@extends('layouts.app')
@section('title', 'Isi Survei')
@push('head')
<style>
    .survey-section { display: none; }
    .survey-section.active { display: block; }
    .likert { display: flex; gap: .4rem; justify-content: space-between; max-width: 22rem; }
    .likert .btn { flex: 1; }
    .likert-labels { display: flex; justify-content: space-between; max-width: 22rem; font-size: .75rem; color: #6c757d; }
    .question.missing { border-left: 4px solid #DC3545; padding-left: .75rem; }
</style>
@endpush
@section('content')
@php $total = count($sections) + 1; @endphp
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="mb-3">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span id="step-label">Bagian 1 dari {{ $total }}</span>
                <span><i class="bi bi-lock-fill"></i> Jawaban anonim</span>
            </div>
            <div class="progress" style="height: 8px"><div id="progress" class="progress-bar bg-success" style="width: 0%"></div></div>
        </div>

        <form method="POST" action="{{ route('survey.submit') }}" id="survey-form" novalidate>
            @csrf
            @foreach ($sections as $i => $section)
                <div class="card shadow-sm border-0 survey-section" data-step="{{ $i }}">
                    <div class="card-body p-4">
                        <h2 class="h5 text-di mb-1">{{ $section['title'] }}</h2>
                        @if ($section['description'])<p class="text-muted small">{{ $section['description'] }}</p>@endif
                        <hr>
                        @foreach ($section['questions'] as $q)
                            @php $name = "answers[{$q->id}]"; $old = old("answers.{$q->id}"); @endphp
                            <div class="question mb-4 @error("answers.{$q->id}") missing @enderror" data-required="{{ $q->is_required && $q->type !== 'text' ? 1 : 0 }}" data-qid="{{ $q->id }}" data-text="{{ $q->text }}">
                                <p class="mb-2 fw-medium">{{ $q->text }} @if($q->is_required && $q->type !== 'text')<span class="text-danger">*</span>@endif</p>
                                @switch($q->type)
                                    @case('likert')
                                        <div class="likert" role="radiogroup">
                                            @for ($v = 1; $v <= 5; $v++)
                                                <input type="radio" class="btn-check" name="{{ $name }}" id="q{{ $q->id }}_{{ $v }}" value="{{ $v }}" data-label="{{ $v }}" @checked((string) $old === (string) $v)>
                                                <label class="btn btn-outline-primary" for="q{{ $q->id }}_{{ $v }}">{{ $v }}</label>
                                            @endfor
                                        </div>
                                        <div class="likert-labels mt-1"><span>Sangat tidak setuju</span><span>Sangat setuju</span></div>
                                        @break
                                    @case('yes_no')
                                        <div class="d-flex gap-2">
                                            <input type="radio" class="btn-check" name="{{ $name }}" id="q{{ $q->id }}_1" value="1" data-label="Ya" @checked((string) $old === '1')>
                                            <label class="btn btn-outline-primary px-4" for="q{{ $q->id }}_1">Ya</label>
                                            <input type="radio" class="btn-check" name="{{ $name }}" id="q{{ $q->id }}_0" value="0" data-label="Tidak" @checked((string) $old === '0')>
                                            <label class="btn btn-outline-primary px-4" for="q{{ $q->id }}_0">Tidak</label>
                                        </div>
                                        @break
                                    @case('single_choice')
                                        @foreach ($q->options as $opt)
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="{{ $name }}" id="q{{ $q->id }}_{{ $loop->index }}" value="{{ $opt->value }}" data-label="{{ $opt->label }}" @checked((string) $old === (string) $opt->value)>
                                                <label class="form-check-label" for="q{{ $q->id }}_{{ $loop->index }}">{{ $opt->label }}</label>
                                            </div>
                                        @endforeach
                                        @break
                                    @default
                                        <textarea class="form-control" name="{{ $name }}" rows="3" maxlength="2000" placeholder="Opsional. Mohon tidak menuliskan nama atau identitas siapa pun.">{{ $old }}</textarea>
                                @endswitch
                                @error("answers.{$q->id}")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="card shadow-sm border-0 survey-section" data-step="{{ count($sections) }}">
                <div class="card-body p-4">
                    <h2 class="h5 text-di">Tinjau Jawaban</h2>
                    <p class="text-muted small">Periksa kembali jawaban Anda. Setelah dikirim, survei tidak dapat diubah.</p>
                    <div id="review" class="small"></div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <button type="button" class="btn btn-outline-secondary" id="btn-prev"><i class="bi bi-chevron-left"></i> Kembali</button>
                <button type="button" class="btn btn-di" id="btn-next">Lanjut <i class="bi bi-chevron-right"></i></button>
                <button type="submit" class="btn btn-success d-none" id="btn-submit"><i class="bi bi-send"></i> Kirim Survei</button>
            </div>
            <div id="step-error" class="text-danger small mt-2 d-none">Masih ada pertanyaan wajib yang belum dijawab.</div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const sections = [...document.querySelectorAll('.survey-section')];
    const total = sections.length;
    const form = document.getElementById('survey-form');
    const prev = document.getElementById('btn-prev'), next = document.getElementById('btn-next'), submit = document.getElementById('btn-submit');
    const errBox = document.getElementById('step-error');
    let step = 0;

    // Jika ada error validasi dari server, buka bagian pertama yang bermasalah
    const firstMissing = document.querySelector('.question.missing');
    if (firstMissing) step = sections.indexOf(firstMissing.closest('.survey-section'));

    const answered = (q) => {
        const checked = q.querySelector('input:checked');
        const text = q.querySelector('textarea');
        return !!checked || (text && text.value.trim() !== '');
    };

    const stepValid = (i) => {
        let ok = true;
        sections[i].querySelectorAll('.question[data-required="1"]').forEach((q) => {
            const has = answered(q);
            q.classList.toggle('missing', !has);
            if (!has) ok = false;
        });
        return ok;
    };

    const buildReview = () => {
        const box = document.getElementById('review');
        box.innerHTML = '';
        sections.slice(0, -1).forEach((sec) => {
            const h = document.createElement('h3');
            h.className = 'h6 mt-3';
            h.textContent = sec.querySelector('h2').textContent;
            box.appendChild(h);
            const ul = document.createElement('ul');
            sec.querySelectorAll('.question').forEach((q) => {
                const li = document.createElement('li');
                const checked = q.querySelector('input:checked');
                const text = q.querySelector('textarea');
                const value = checked ? checked.dataset.label : (text && text.value.trim() ? text.value.trim() : '(tidak diisi)');
                li.textContent = q.dataset.text + ': ' + value;
                ul.appendChild(li);
            });
            box.appendChild(ul);
        });
    };

    const show = () => {
        sections.forEach((s, i) => s.classList.toggle('active', i === step));
        document.getElementById('step-label').textContent = `Bagian ${step + 1} dari ${total}`;
        document.getElementById('progress').style.width = `${Math.round((step / (total - 1)) * 100)}%`;
        prev.disabled = step === 0;
        const last = step === total - 1;
        next.classList.toggle('d-none', last);
        submit.classList.toggle('d-none', !last);
        if (last) buildReview();
        errBox.classList.add('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    next.addEventListener('click', () => {
        if (!stepValid(step)) { errBox.classList.remove('d-none'); return; }
        step = Math.min(step + 1, total - 1);
        show();
    });
    prev.addEventListener('click', () => { step = Math.max(step - 1, 0); show(); });
    form.addEventListener('change', (e) => {
        const q = e.target.closest('.question');
        if (q && answered(q)) q.classList.remove('missing');
    });
    form.addEventListener('submit', (e) => {
        for (let i = 0; i < total - 1; i++) {
            if (!stepValid(i)) { e.preventDefault(); step = i; show(); errBox.classList.remove('d-none'); return; }
        }
        submit.disabled = true;
    });
    show();
})();
</script>
@endpush
