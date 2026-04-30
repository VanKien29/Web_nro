<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\MilestoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MilestoneController extends Controller
{
    public function __construct(private readonly MilestoneService $milestones)
    {
    }

    public function index(Request $request, string $type): JsonResponse
    {
        return $this->jsonResult($this->milestones->list($request, $type));
    }

    public function show(string $type, int $id): JsonResponse
    {
        return $this->jsonResult($this->milestones->get($type, $id));
    }

    public function store(Request $request, string $type): JsonResponse
    {
        return $this->jsonResult($this->milestones->create($request, $type));
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        return $this->jsonResult($this->milestones->update($request, $type, $id));
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        return $this->jsonResult($this->milestones->delete($type, $id));
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
