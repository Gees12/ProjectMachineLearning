<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AnalysisException;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Services\AnalysisService;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function index(Survey $survey)
    {
        $runs = $survey->analysisRuns()->with('runner')->get();
        $validCount = $survey->responses()->where('is_valid', true)->count();

        return view('admin.analyses.index', compact('survey', 'runs', 'validCount'));
    }

    public function run(Request $request, Survey $survey, AnalysisService $service)
    {
        try {
            $run = $service->run($survey, $request->user());
        } catch (AnalysisException $e) {
            return back()->with('error', 'Analisis gagal: '.$e->getMessage());
        }

        return redirect()->route('admin.analyses.index', $survey)
            ->with('success', "Analisis selesai: {$run->n_responses} respons dianalisis dengan model {$run->model_version}.");
    }
}
