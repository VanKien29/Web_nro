<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\GiftcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GiftcodeController extends Controller
{
    public function __construct(private readonly GiftcodeService $giftcodes)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->giftcodes->list($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->giftcodes->get($id));
    }

    public function activity(int $id): JsonResponse
    {
        return $this->jsonResult($this->giftcodes->activity($id));
    }

    public function clone(int $id): JsonResponse
    {
        return $this->jsonResult($this->giftcodes->clone($id));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->jsonResult($this->giftcodes->create($request));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->jsonResult($this->giftcodes->update($request, $id));
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->jsonResult($this->giftcodes->delete($id));
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
