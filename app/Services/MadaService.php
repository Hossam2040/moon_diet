<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MadaService
{
    protected string $merchantId;
    protected string $secretKey;
    protected string $endpoint;
    protected ?string $callbackUrl;
    protected bool $mockLocal;

    public function __construct()
    {
        $this->merchantId = (string) config('services.mada.merchant_id');
        $this->secretKey = (string) config('services.mada.secret_key');
        $this->endpoint = rtrim((string) config('services.mada.endpoint'), '/');
        $this->callbackUrl = config('services.mada.callback_url');
        $this->mockLocal = (bool) config('services.mada.mock_local', app()->environment(['local','testing']));
    }

    public function createPaymentIntent(string $reference, string $description, string $amountSar, array $metadata = []): array
    {
        // In local/testing, avoid HTTP self-call deadlocks and just mock
        if ($this->mockLocal) {
            $id = 'dev_mada_' . uniqid();
            return [
                'intent_id' => $id,
                'redirect_url' => url('/api/dev/mada/redirect?intent_id=' . $id),
            ];
        }

        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => $amountSar,
            'currency' => 'SAR',
            'reference' => $reference,
            'description' => $description,
            'callback_url' => $this->callbackUrl,
            'metadata' => $metadata,
        ];

        $payload['signature'] = $this->sign($payload);

        $response = Http::acceptJson()->post($this->endpoint . '/payments', $payload);

        if (!$response->ok()) {
            throw new \RuntimeException('Mada create payment failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'intent_id' => (string) ($data['id'] ?? ''),
            'redirect_url' => (string) ($data['redirect_url'] ?? ''),
        ];
    }

    public function verifySignature(array $data, string $signature): bool
    {
        // Never sign the incoming signature itself if present
        unset($data['signature']);

        // In local/testing allow a simpler canonical set of fields to ease manual testing
        if ($this->mockLocal) {
            $allowed = ['amount', 'currency', 'id', 'status'];
            $filtered = [];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $data)) {
                    $filtered[$k] = (string) $data[$k];
                }
            }
            $expected = $this->sign($filtered);
            return hash_equals($expected, $signature);
        }

        $expected = $this->sign($data);
        return hash_equals($expected, $signature);
    }

    protected function sign(array $data): string
    {
        ksort($data);
        $serialized = http_build_query($data);
        return hash_hmac('sha256', $serialized, $this->secretKey);
    }
}


