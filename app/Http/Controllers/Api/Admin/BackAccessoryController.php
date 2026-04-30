<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\BackAccessoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BackAccessoryController extends Controller
{
    public function __construct(private readonly BackAccessoryService $backAccessories)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->backAccessories->list($request));
    }

    public function store(Request $request): JsonResponse
    {
        $this->validatePayload($request, creating: true);

        return $this->jsonResult($this->backAccessories->create($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->backAccessories->get($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->validatePayload($request, creating: false);

        return $this->jsonResult($this->backAccessories->update($request, $id));
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->jsonResult($this->backAccessories->delete($id));
    }

    private function validatePayload(Request $request, bool $creating): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'item_id' => 'nullable|integer|min:1',
            'gender' => 'nullable|integer|min:0|max:3',
            'gold' => 'nullable|integer',
            'gem' => 'nullable|integer',
            'icon_x4' => 'nullable',
            'icon_x4_payload' => 'nullable|string',
            'item_icon_x4' => 'nullable',
        ];

        if ($creating) {
            $rules['flag_id'] = 'nullable|integer|min:0|max:255';
        }

        $request->validate($rules);
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
