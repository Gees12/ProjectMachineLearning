<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Factor;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FactorController extends Controller
{
    public function store(Request $request, Survey $survey)
    {
        if ($survey->hasResponses()) {
            return back()->with('error', 'Survei sudah memiliki respons; faktor tidak dapat diubah.');
        }
        $survey->factors()->create($this->validated($request, $survey));

        return back()->with('success', 'Faktor ditambahkan.');
    }

    public function update(Request $request, Survey $survey, Factor $factor)
    {
        abort_unless($factor->survey_id === $survey->id, 404);
        if ($survey->hasResponses()) {
            return back()->with('error', 'Survei sudah memiliki respons; faktor tidak dapat diubah.');
        }
        $factor->update($this->validated($request, $survey, $factor));

        return back()->with('success', 'Faktor diperbarui.');
    }

    public function destroy(Survey $survey, Factor $factor)
    {
        abort_unless($factor->survey_id === $survey->id, 404);
        if ($survey->hasResponses()) {
            return back()->with('error', 'Survei sudah memiliki respons; faktor tidak dapat dihapus.');
        }
        if ($factor->questions()->exists()) {
            return back()->with('error', 'Hapus atau pindahkan pertanyaan pada faktor ini terlebih dahulu.');
        }
        $factor->delete();

        return back()->with('success', 'Faktor dihapus.');
    }

    private function validated(Request $request, Survey $survey, ?Factor $factor = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('factors')->where('survey_id', $survey->id)->ignore($factor?->id)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ], ['code.regex' => 'Kode memakai huruf besar, angka, dan garis bawah, mis. F_MAK.']);
    }
}
