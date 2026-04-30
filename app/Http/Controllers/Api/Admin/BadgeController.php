<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\BadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BadgeController extends Controller
{
    public function __construct(private readonly BadgeService $badges)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->badges->list($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->badges->get($id));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->jsonResult($this->badges->create($request));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->jsonResult($this->badges->update($request, $id));
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->jsonResult($this->badges->delete($id));
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
