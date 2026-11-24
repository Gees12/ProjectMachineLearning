<?php

namespace App\Http\Controllers;

use App\Exceptions\MlServiceException;
use App\Models\Survey;
use App\Services\DashboardService;
use App\Services\MlClient;
use Illuminate\Http\Request;

/** Simulasi skenario perbaikan (FR-066, UC-08). Tidak menyimpan data apa pun. */
class SimulationController extends Controller
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    private function context(): array
    {
        $survey = Survey::where('status', 'closed')->has('analysisRuns')->orderByDesc('close_at')->first()
            ?? Survey::where('status', 'closed')->orderByDesc('close_at')->first();
        $baseline = $survey ? $this->dashboard->baselineFeatures($survey) : null;
        $labels = $survey ? $this->dashboard->labels($survey) : [];
        $factorCodes = $survey ? $survey->factors()->pluck('code')->all() : [];

        return compact('survey', 'baseline', 'labels', 'factorCodes');
    }

    public function index()
    {
        return view('simulation.index', $this->context() + ['result' => null, 'scenario' => null]);
    }

    public function run(Request $request, MlClient $ml)
    {
        $ctx = $this->context();
        abort_unless($ctx['baseline'], 404);

        $rules = [];
        foreach ($ctx['factorCodes'] as $code) {
            $rules["scenario.{$code}"] = ['required', 'numeric', 'between:1,5'];
        }
        $input = $request->validate($rules)['scenario'];

        $scenario = $ctx['baseline'];
        foreach ($input as $code => $value) {
            $scenario[$code] = round((float) $value, 2);
        }

        try {
            $result = $ml->simulate($ctx['baseline'], $scenario);
        } catch (MlServiceException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return view('simulation.index', $ctx + compact('result', 'scenario'));
    }
}
