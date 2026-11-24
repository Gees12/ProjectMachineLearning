<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    public function index()
    {
        $surveys = Survey::withCount(['responses', 'questions'])->orderByDesc('open_at')->get();

        return view('admin.surveys.index', compact('surveys'));
    }

    public function create()
    {
        return view('admin.surveys.form', ['survey' => new Survey([
            'open_at' => now()->startOfDay(), 'close_at' => now()->addWeeks(2)->endOfDay(),
        ])]);
    }

    public function store(Request $request)
    {
        $survey = Survey::create($this->validated($request) + ['status' => 'draft', 'created_by' => $request->user()->id]);

        return redirect()->route('admin.surveys.show', $survey)->with('success', 'Survei dibuat. Tambahkan faktor dan pertanyaan.');
    }

    public function show(Survey $survey)
    {
        $survey->load(['factors', 'questions.factor', 'questions.options']);
        $locked = $survey->hasResponses();

        return view('admin.surveys.show', compact('survey', 'locked'));
    }

    public function edit(Survey $survey)
    {
        return view('admin.surveys.form', compact('survey'));
    }

    public function update(Request $request, Survey $survey)
    {
        $survey->update($this->validated($request));

        return redirect()->route('admin.surveys.show', $survey)->with('success', 'Survei diperbarui.');
    }

    public function destroy(Survey $survey)
    {
        if ($survey->hasResponses()) {
            return back()->with('error', 'Survei yang sudah memiliki respons tidak dapat dihapus.');
        }
        $survey->delete();

        return redirect()->route('admin.surveys.index')->with('success', 'Survei dihapus.');
    }

    public function open(Survey $survey)
    {
        if (Survey::where('status', 'active')->whereKeyNot($survey->id)->exists()) {
            return back()->with('error', 'Masih ada survei lain yang aktif. Tutup survei tersebut terlebih dahulu.');
        }
        if ($survey->targetQuestions()->count() !== 1) {
            return back()->with('error', 'Survei harus memiliki tepat satu pertanyaan label target (Ya/Tidak).');
        }
        if ($survey->close_at->isPast()) {
            return back()->with('error', 'Tanggal tutup sudah lewat. Ubah tanggal tutup terlebih dahulu.');
        }
        $survey->update(['status' => 'active']);

        return back()->with('success', 'Survei dibuka untuk mahasiswa.');
    }

    public function close(Survey $survey)
    {
        $survey->update(['status' => 'closed']);

        return back()->with('success', 'Survei ditutup. Analisis ML sekarang dapat dijalankan.');
    }

    /** Menyalin survei (faktor, pertanyaan, opsi) sebagai draft baru (FR-027). */
    public function duplicate(Request $request, Survey $survey)
    {
        $copy = DB::transaction(function () use ($request, $survey) {
            $copy = $survey->replicate(['status', 'created_by']);
            $copy->fill(['title' => $survey->title.' (Salinan)', 'status' => 'draft', 'created_by' => $request->user()->id,
                'open_at' => now()->startOfDay(), 'close_at' => now()->addWeeks(2)->endOfDay()])->save();

            $factorMap = [];
            foreach ($survey->factors as $factor) {
                $factorMap[$factor->id] = $copy->factors()->create($factor->only(['code', 'name', 'description', 'sort_order']))->id;
            }
            foreach ($survey->questions()->with('options')->get() as $q) {
                $newQ = $copy->questions()->create($q->only(['code', 'text', 'type', 'ml_role', 'is_required', 'sort_order'])
                    + ['factor_id' => $q->factor_id ? $factorMap[$q->factor_id] ?? null : null]);
                foreach ($q->options as $opt) {
                    $newQ->options()->create($opt->only(['label', 'value', 'sort_order']));
                }
            }

            return $copy;
        });

        return redirect()->route('admin.surveys.show', $copy)->with('success', 'Survei berhasil disalin.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'open_at' => ['required', 'date'],
            'close_at' => ['required', 'date', 'after:open_at'],
        ]);
    }
}
