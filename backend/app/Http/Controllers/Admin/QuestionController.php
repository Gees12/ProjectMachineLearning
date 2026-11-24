<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function create(Survey $survey)
    {
        return $this->form($survey, new Question(['type' => 'likert', 'ml_role' => 'factor_item', 'is_required' => true,
            'sort_order' => ($survey->questions()->max('sort_order') ?? 0) + 1]));
    }

    public function edit(Survey $survey, Question $question)
    {
        abort_unless($question->survey_id === $survey->id, 404);
        $question->load('options');

        return $this->form($survey, $question);
    }

    public function store(Request $request, Survey $survey)
    {
        return $this->save($request, $survey, new Question(['survey_id' => $survey->id]));
    }

    public function update(Request $request, Survey $survey, Question $question)
    {
        abort_unless($question->survey_id === $survey->id, 404);

        return $this->save($request, $survey, $question);
    }

    public function destroy(Survey $survey, Question $question)
    {
        abort_unless($question->survey_id === $survey->id, 404);
        if ($survey->hasResponses()) {
            return back()->with('error', 'Survei sudah memiliki respons; pertanyaan tidak dapat dihapus.');
        }
        $question->delete();

        return back()->with('success', 'Pertanyaan dihapus.');
    }

    private function form(Survey $survey, Question $question)
    {
        if ($survey->hasResponses()) {
            return redirect()->route('admin.surveys.show', $survey)->with('error', 'Survei sudah memiliki respons; pertanyaan terkunci.');
        }
        $optionsText = $question->options->map(fn ($o) => "{$o->value}|{$o->label}")->implode("\n");

        return view('admin.surveys.question-form', compact('survey', 'question', 'optionsText'));
    }

    private function save(Request $request, Survey $survey, Question $question)
    {
        if ($survey->hasResponses()) {
            return redirect()->route('admin.surveys.show', $survey)->with('error', 'Survei sudah memiliki respons; pertanyaan terkunci (FR-025).');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('questions')->where('survey_id', $survey->id)->ignore($question->id)],
            'text' => ['required', 'string', 'max:1000'],
            'type' => ['required', Rule::in(array_keys(Question::TYPES))],
            'ml_role' => ['required', Rule::in(array_keys(Question::ML_ROLES))],
            'factor_id' => ['nullable', Rule::exists('factors', 'id')->where('survey_id', $survey->id)],
            'is_required' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'options' => ['nullable', 'string', 'max:2000'],
        ], ['code.regex' => 'Kode memakai huruf besar, angka, dan garis bawah, mis. F_MAK_1.']);

        $data['is_required'] = $request->boolean('is_required');
        $errors = $this->consistencyErrors($survey, $question, $data);
        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        DB::transaction(function () use ($question, $data) {
            $question->fill(collect($data)->except('options')->all())->save();
            $question->options()->delete();
            if ($data['type'] === 'single_choice') {
                foreach ($this->parseOptions($data['options'] ?? '') as $i => [$value, $label]) {
                    $question->options()->create(['value' => $value, 'label' => $label, 'sort_order' => $i]);
                }
            }
        });

        return redirect()->route('admin.surveys.show', $survey)->with('success', 'Pertanyaan disimpan.');
    }

    private function consistencyErrors(Survey $survey, Question $question, array $data): array
    {
        $errors = [];
        if ($data['ml_role'] === 'factor_item' && ($data['type'] !== 'likert' || empty($data['factor_id']))) {
            $errors['ml_role'] = 'Item faktor harus bertipe Likert dan memiliki faktor.';
        }
        if ($data['ml_role'] !== 'factor_item' && ! empty($data['factor_id'])) {
            $errors['factor_id'] = 'Hanya item faktor yang boleh dikaitkan dengan faktor.';
        }
        if ($data['ml_role'] === 'target') {
            if ($data['type'] !== 'yes_no') {
                $errors['type'] = 'Pertanyaan label target harus bertipe Ya/Tidak.';
            }
            if ($survey->targetQuestions()->whereKeyNot($question->id ?? 0)->exists()) {
                $errors['ml_role'] = 'Survei sudah memiliki pertanyaan label target. Hanya boleh satu (FR-024).';
            }
        }
        if ($data['type'] === 'single_choice' && count($this->parseOptions($data['options'] ?? '')) < 2) {
            $errors['options'] = 'Pilihan tunggal membutuhkan minimal 2 opsi (format: nilai|label per baris).';
        }

        return $errors;
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function parseOptions(string $text): array
    {
        return collect(preg_split('/\r?\n/', $text))
            ->map(fn ($line) => trim($line))->filter()
            ->map(function ($line) {
                [$value, $label] = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);

                return [$value, $label ?: $value];
            })->values()->all();
    }
}
