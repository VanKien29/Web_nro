<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GameRuntimeService
{
    public function reloadShop(): array
    {
        return $this->post('/internal/runtime/shop/reload', []);
    }

    public function bosses(): array
    {
        return $this->request('GET', '/internal/runtime/bosses', []);
    }

    public function createBoss(array $payload): array
    {
        return $this->post('/internal/runtime/bosses/create', $payload);
    }

    public function bossAction(array $payload): array
    {
        return $this->post('/internal/runtime/bosses/action', $payload);
    }

    public function updateBoss(array $payload): array
    {
        return $this->post('/internal/runtime/bosses/update', $payload);
    }

    public function bossConfigs(): array
    {
        return $this->request('GET', '/internal/runtime/bosses/configs', []);
    }

    public function saveBossConfig(array $payload): array
    {
        return $this->post('/internal/runtime/bosses/configs', $payload);
    }

    public function mapMobs(): array
    {
        return $this->request('GET', '/internal/runtime/map-mobs', []);
    }

    public function saveMapMobs(array $payload): array
    {
        return $this->post('/internal/runtime/map-mobs', $payload);
    }

    public function buffMail(array $payload): array
    {
        return $this->post('/internal/runtime/buffs/mail', $payload);
    }

    public function buffAccount(array $payload): array
    {
        return $this->post('/internal/runtime/buffs/account', $payload);
    }

    public function health(): array
    {
        return $this->request('GET', '/internal/runtime/health', []);
    }

    private function post(string $path, array $payload): array
    {
        return $this->request('POST', $path, $payload);
    }

    private function request(string $method, string $path, array $payload): array
    {
        $baseUrl = rtrim((string) config('services.game_runtime.base_url'), '/');
        $key = (string) config('services.game_runtime.key');
        $secret = (string) config('services.game_runtime.secret');

        if ($baseUrl === '' || $key === '' || strlen($secret) < 32) {
            throw new RuntimeException('Game runtime API chưa được cấu hình đúng.');
        }

        $body = $method === 'GET'
            ? ''
            : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));
        $canonical = strtoupper($method) . "\n" . $path . "\n" . $timestamp . "\n" . $nonce . "\n" . $body;
        $signature = hash_hmac('sha256', $canonical, $secret);

        $request = Http::timeout((int) config('services.game_runtime.timeout', 5))
            ->acceptJson()
            ->withHeaders([
                'X-Game-Admin-Key' => $key,
                'X-Game-Admin-Timestamp' => $timestamp,
                'X-Game-Admin-Nonce' => $nonce,
                'X-Game-Admin-Signature' => $signature,
            ]);

        $response = $method === 'GET'
            ? $request->get($baseUrl . $path)
            : $request->withBody($body, 'application/json')->send($method, $baseUrl . $path);

        $data = $response->json();
        if (!is_array($data)) {
            $data = [
                'ok' => false,
                'code' => 'BAD_RUNTIME_RESPONSE',
                'message' => $response->body(),
            ];
        }

        $data['http_status'] = $response->status();
        return $data;
    }
}
