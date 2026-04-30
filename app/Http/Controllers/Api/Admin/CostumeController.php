<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\CostumeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CostumeController extends Controller
{
    public function __construct(private readonly CostumeService $costumes)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->costumes->list($request));
    }

    public function store(Request $request): JsonResponse
    {
        $this->validatePayload($request, creating: true);

        return $this->jsonResult($this->costumes->create($request));
    }

    public function show(int $id): JsonResponse
    {
        return $this->jsonResult($this->costumes->get($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->validatePayload($request, creating: false);

        return $this->jsonResult($this->costumes->update($request, $id));
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->jsonResult($this->costumes->delete($id));
    }

    private function validatePayload(Request $request, bool $creating): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'gender' => 'nullable|integer|min:0|max:3',
            'head_data' => 'required|string|max:400000',
            'body_data' => 'required|string|max:400000',
            'leg_data' => 'required|string|max:400000',
            'extra_head_data' => 'nullable|string|max:400000',
            'icon_x4' => 'nullable',
            'icon_x4_payload' => 'nullable|string',
            'item_icon_x4' => 'nullable',
            'avatar_x4' => 'nullable',
        ];

        if ($creating) {
            $rules['item_id'] = 'nullable|integer|min:1';
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
