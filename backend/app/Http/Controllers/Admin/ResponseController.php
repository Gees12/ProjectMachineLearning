<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseController extends Controller
{
    public function index(Survey $survey)
    {
        $responses = $survey->responses()->with('feature')
            ->orderBy('is_valid')->orderByDesc('submitted_date')
            ->paginate(50);

        $stats = [
            'total' => $survey->responses()->count(),
            'valid' => $survey->responses()->where('is_valid', true)->count(),
            'target' => config('dorminsight.min_valid_responses'),
            'population' => config('dorminsight.population'),
            'students' => User::where('role', 'mahasiswa')->where('is_active', true)->count(),
        ];

        return view('admin.responses.index', compact('survey', 'responses', 'stats'));
    }

    public function toggleValidity(Response $response)
    {
        $response->update(['is_valid' => ! $response->is_valid]);

        return back()->with('success', 'Status respons diperbarui menjadi '.($response->is_valid ? 'valid' : 'dikecualikan').'.');
    }

    /** Ekspor CSV anonim sesuai docs/04_Machine_Learning/Dataset_Documentation.md (FR-044). */
    public function export(Survey $survey): StreamedResponse
    {
        $questions = $survey->questions()->get(['id', 'code', 'type']);
        $filename = 'survey_export_'.$survey->id.'_'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($survey, $questions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['response_id'], $questions->pluck('code')->all(), ['duration_seconds', 'is_valid']));

            $survey->responses()->orderBy('id')->chunk(200, function ($responses) use ($out, $questions) {
                $answers = Answer::whereIn('response_id', $responses->pluck('id'))->get()->groupBy('response_id');
                foreach ($responses as $response) {
                    $byQuestion = ($answers[$response->id] ?? collect())->keyBy('question_id');
                    $row = [$response->id];
                    foreach ($questions as $q) {
                        $a = $byQuestion[$q->id] ?? null;
                        $row[] = match (true) {
                            $a === null => '',
                            in_array($q->type, ['likert', 'yes_no'], true) => (int) $a->value_numeric,
                            default => $a->value_text,
                        };
                    }
                    $row[] = $response->duration_seconds;
                    $row[] = $response->is_valid ? 1 : 0;
                    fputcsv($out, $row);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
