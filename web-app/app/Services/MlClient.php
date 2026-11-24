<?php

namespace App\Services;

use App\Exceptions\MlServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/** Klien HTTP ke ML service. Kontrak: docs/03_System_Design/API_Documentation.md */
class MlClient
{
    protected function http(): PendingRequest
    {
        return Http::baseUrl(config('dorminsight.ml.url'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(15)
            ->timeout(config('dorminsight.ml.timeout'))
            ->withHeaders(['X-API-Key' => (string) config('dorminsight.ml.key')])
            ->retry(2, 1000, fn ($e) => $e instanceof ConnectionException);
    }

    protected function call(string $method, string $uri, array $data = []): array
    {
        try {
            $response = $method === 'get' ? $this->http()->get($uri) : $this->http()->post($uri, $data);

            return $response->throw()->json() ?? [];
        } catch (ConnectionException) {
            throw new MlServiceException('ML service tidak dapat dihubungi. Pastikan service berjalan lalu coba lagi.');
        } catch (RequestException $e) {
            $detail = $e->response->json('detail');
            $detail = is_string($detail) ? $detail : json_encode($detail);
            throw new MlServiceException("ML service mengembalikan error {$e->response->status()}: {$detail}");
        }
    }

    public function health(): array
    {
        return $this->call('get', '/health');
    }

    public function modelInfo(): array
    {
        return $this->call('get', '/model/info');
    }

    /** @param  array<int, array{id: string, features: array}>  $records */
    public function predictBatch(array $records): array
    {
        $predictions = [];
        foreach (array_chunk($records, config('dorminsight.ml.max_records')) as $chunk) {
            $predictions = array_merge($predictions, $this->call('post', '/predict/batch', ['records' => $chunk])['predictions'] ?? []);
        }

        return $predictions;
    }

    /** @param  array<int, array{id: string, features: array}>  $records */
    public function explainGlobal(array $records): array
    {
        $records = array_slice($records, 0, config('dorminsight.ml.max_records'));

        return $this->call('post', '/explain/global', ['records' => $records]);
    }

    public function simulate(array $baseline, array $scenario): array
    {
        return $this->call('post', '/simulate', ['baseline' => $baseline, 'scenario' => $scenario]);
    }
}
