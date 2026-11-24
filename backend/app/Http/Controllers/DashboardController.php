<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    public function index(Request $request)
    {
        $surveys = Survey::whereIn('status', ['active', 'closed'])->orderByDesc('open_at')->get();
        $survey = $surveys->firstWhere('id', (int) $request->query('survey'))
            ?? $surveys->firstWhere('status', 'closed') ?? $surveys->first();

        if (! $survey) {
            return view('dashboard.index', ['surveys' => $surveys, 'd' => null, 'segments' => []]);
        }

        $d = $this->dashboard->data($survey, $request->only(array_keys(DashboardService::SEGMENT_FIELDS)));
        $segments = $this->dashboard->segmentOptions($survey);

        return view('dashboard.index', compact('surveys', 'd', 'segments'));
    }

    public function compare(Request $request)
    {
        $surveys = Survey::whereIn('status', ['active', 'closed'])->orderByDesc('open_at')->get();
        $a = $surveys->firstWhere('id', (int) $request->query('a'));
        $b = $surveys->firstWhere('id', (int) $request->query('b'));

        $rows = [];
        $da = $a ? $this->dashboard->data($a) : null;
        $db = $b ? $this->dashboard->data($b) : null;
        if ($da && $db) {
            $labels = $da['labels'] + $db['labels'];
            foreach (array_unique(array_merge(array_keys($da['factor_means']), array_keys($db['factor_means']))) as $code) {
                $va = $da['factor_means'][$code] ?? null;
                $vb = $db['factor_means'][$code] ?? null;
                $rows[] = ['label' => $labels[$code] ?? $code, 'a' => $va, 'b' => $vb,
                    'delta' => $va !== null && $vb !== null ? round($vb - $va, 2) : null];
            }
        }

        return view('dashboard.compare', compact('surveys', 'a', 'b', 'da', 'db', 'rows'));
    }
}
