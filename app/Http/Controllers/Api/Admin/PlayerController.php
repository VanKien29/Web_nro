<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\PlayerManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlayerController extends Controller
{
    public function __construct(private readonly PlayerManagerService $players)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->players->list($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->players->show($id));
    }

    public function updateStats(Request $request, int $id): JsonResponse
    {
        return $this->jsonResult($this->players->updateStats($request, $id));
    }

    public function inventorySearch(Request $request): JsonResponse
    {
        return $this->jsonResult($this->players->inventorySearch($request));
    }

    public function buffInventory(Request $request, int $id): JsonResponse
    {
        return $this->jsonResult($this->players->buffInventory($request, $id));
    }

    public function revokeInventory(Request $request, int $id): JsonResponse
    {
        return $this->jsonResult($this->players->revokeInventory($request, $id));
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
