<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GameApiService
{
    protected string $baseUrl;
    protected string $topupSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.game_api.base_url', ''), '/');
        $this->topupSecret = config('services.game_api.topup_secret', '');
    }

    public function login(string $username, string $password): array
    {
        $response = Http::timeout(10)
            ->post("{$this->baseUrl}/api/auth/login", compact('username', 'password'));

        return $this->parseResponse($response);
    }

    public function register(string $username, string $password): array
    {
        $response = Http::timeout(10)
            ->post("{$this->baseUrl}/api/auth/register", compact('username', 'password'));

        return $this->parseResponse($response);
    }

    public function getProfileByToken(string $token): array
    {
        $response = Http::timeout(10)
            ->withToken($token)
            ->get("{$this->baseUrl}/api/profile");

        return $this->parseResponse($response);
    }

    public function changePassword(string $token, string $newPassword): array
    {
        $response = Http::timeout(10)
            ->withToken($token)
            ->post("{$this->baseUrl}/api/auth/change-password", [
                'new_password' => $newPassword,
            ]);

        return $this->parseResponse($response);
    }

    public function activateAccount(string $token): array
    {
        $response = Http::timeout(10)
            ->withToken($token)
            ->post("{$this->baseUrl}/api/auth/activate");

        return $this->parseResponse($response);
    }

    public function getBxh(): array
    {
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/api/bxh");

        return $this->parseResponse($response);
    }

    public function getGiftcodes(): array
    {
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/api/giftcode");

        return $this->parseResponse($response);
    }

    public function credit(string $username, int $amount, string $transId, bool $addBothFields = false): array
    {
        $response = Http::timeout(10)
            ->withHeaders(['X-Topup-Secret' => $this->topupSecret])
            ->post("{$this->baseUrl}/api/topup/credit", [
                'username' => $username,
                'amount' => $amount,
                'trans_id' => $transId,
                'add_both_fields' => $addBothFields,
            ]);

        return $this->parseResponse($response);
    }

    public function getItems(): array
    {
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/api/items");

        return $this->parseResponse($response);
    }

    public function getItemOptions(int $itemId): array
    {
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/api/items/{$itemId}/options");

        return $this->parseResponse($response);
    }

    protected function parseResponse($response): array
    {
        if ($response->failed()) {
            return ['status' => 'error', 'message' => 'Không thể kết nối đến server game.'];
        }

        $body = trim($response->body());
        // Remove BOM if present
        if (str_starts_with($body, "\xef\xbb\xbf")) {
            $body = substr($body, 3);
        }

        $json = json_decode($body, true);
        return is_array($json) ? $json : [];
    }
}