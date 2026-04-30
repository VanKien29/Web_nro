<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShopController extends Controller
{
    public function __construct(private readonly ShopService $shops)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->shops->list($request));
    }

    public function tab(int $tabId): JsonResponse
    {
        return $this->jsonResult($this->shops->getTab($tabId));
    }

    public function updateTab(Request $request, int $tabId): JsonResponse
    {
        return $this->jsonResult($this->shops->updateTab($request, $tabId));
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
