@if ($run && $run->isExperimental())
    <div class="alert alert-warning d-flex gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>
            <strong>Hasil eksperimental.</strong>
            @if (($run->metrics['data_source'] ?? 'dummy') !== 'real')
                Model {{ $run->model_version }} dilatih dengan <strong>data dummy</strong>, bukan data survei Crystal Dormitory.
            @else
                Jumlah data training belum memenuhi batas minimum.
            @endif
            Hasil prediksi dan faktor dominan belum boleh dijadikan dasar keputusan.
        </div>
    </div>
@endif
