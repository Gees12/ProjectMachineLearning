<?php

namespace App\Http\Controllers\Student;

use App\Exceptions\SubmissionException;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveySubmission;
use App\Services\SubmissionService;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    private function state(Request $request): array
    {
        $survey = Survey::active();
        $submitted = $survey
            ? SurveySubmission::where('survey_id', $survey->id)->where('user_id', $request->user()->id)->exists()
            : false;

        return [$survey, $submitted];
    }

    public function index(Request $request)
    {
        [$survey, $submitted] = $this->state($request);

        return view('student.index', compact('survey', 'submitted'));
    }

    public function consent(Request $request)
    {
        [$survey, $submitted] = $this->state($request);
        if (! $survey || ! $survey->isOpenForSubmission() || $submitted) {
            return redirect()->route('survey.index');
        }

        return view('student.consent', compact('survey'));
    }

    public function start(Request $request)
    {
        $request->validate(['agree' => ['accepted']], ['agree.accepted' => 'Anda perlu menyetujui pernyataan untuk melanjutkan.']);
        [$survey, $submitted] = $this->state($request);
        if (! $survey || ! $survey->isOpenForSubmission() || $submitted) {
            return redirect()->route('survey.index');
        }

        // Waktu mulai disimpan di session saja (bukan di database) untuk menghitung durasi
        $request->session()->put("survey_started.{$survey->id}", now()->timestamp);

        return redirect()->route('survey.fill');
    }

    public function fill(Request $request)
    {
        [$survey, $submitted] = $this->state($request);
        if (! $survey || ! $survey->isOpenForSubmission() || $submitted) {
            return redirect()->route('survey.index');
        }
        if (! $request->session()->has("survey_started.{$survey->id}")) {
            return redirect()->route('survey.consent');
        }

        $questions = $survey->questions()->with(['factor', 'options'])->get();
        $sections = $this->sections($survey, $questions);

        return view('student.fill', compact('survey', 'sections'));
    }

    public function submit(Request $request, SubmissionService $service)
    {
        [$survey, $submitted] = $this->state($request);
        if (! $survey || $submitted) {
            return redirect()->route('survey.index');
        }

        $questions = $survey->questions()->with('options')->get();
        $rules = [];
        foreach ($questions as $q) {
            $base = $q->is_required ? ['required'] : ['nullable'];
            $rules["answers.{$q->id}"] = match ($q->type) {
                'likert' => [...$base, 'integer', 'between:1,5'],
                'yes_no' => [...$base, 'in:0,1'],
                'single_choice' => [...$base, 'in:'.$q->options->pluck('value')->implode(',')],
                default => ['nullable', 'string', 'max:2000'],
            };
        }
        $data = $request->validate($rules, ['answers.*.required' => 'Pertanyaan ini wajib dijawab.']);

        $started = (int) $request->session()->get("survey_started.{$survey->id}", now()->timestamp);
        $duration = now()->timestamp - $started;

        try {
            $service->submit($survey, $request->user(), $data['answers'] ?? [], $duration);
        } catch (SubmissionException $e) {
            return redirect()->route('survey.index')->with('error', $e->getMessage());
        }

        $request->session()->forget("survey_started.{$survey->id}");

        return redirect()->route('survey.thanks');
    }

    public function thanks()
    {
        return view('student.thanks');
    }

    /** Bagian survei: satu bagian per faktor, lalu "Tentang Anda" dan "Penutup". */
    private function sections(Survey $survey, $questions): array
    {
        $sections = [];
        foreach ($survey->factors as $factor) {
            $qs = $questions->where('factor_id', $factor->id)->values();
            if ($qs->isNotEmpty()) {
                $sections[] = ['title' => $factor->name, 'description' => $factor->description, 'questions' => $qs];
            }
        }
        $profile = $questions->whereNull('factor_id')->where('ml_role', 'feature')->values();
        if ($profile->isNotEmpty()) {
            $sections[] = ['title' => 'Tentang Anda', 'description' => null, 'questions' => $profile];
        }
        $closing = $questions->whereNull('factor_id')->whereIn('ml_role', ['descriptive', 'target', 'open'])->values();
        if ($closing->isNotEmpty()) {
            $sections[] = ['title' => 'Penutup', 'description' => null, 'questions' => $closing];
        }

        return $sections;
    }
}
