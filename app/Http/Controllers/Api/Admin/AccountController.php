<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accounts)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->accounts->list($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->accounts->get($id));
    }

    public function playerFull(int $id): JsonResponse
    {
        return $this->jsonResult($this->accounts->playerFull($id));
    }

    public function playerSection(int $id, string $section): JsonResponse
    {
        return $this->jsonResult($this->accounts->playerSection($id, $section));
    }

    public function activity(int $id): JsonResponse
    {
        return $this->jsonResult($this->accounts->activity($id));
    }

    public function updateBadges(Request $request, int $id): JsonResponse
    {
        return $this->jsonResult($this->accounts->updateBadges($request, $id));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->jsonResult($this->accounts->create($request));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->jsonResult($this->accounts->update($request, $id));
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->jsonResult($this->accounts->delete($id));
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
