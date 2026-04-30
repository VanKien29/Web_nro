<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\TitleItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TitleItemController extends Controller
{
    public function __construct(private readonly TitleItemService $titleItems)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->jsonResult($this->titleItems->list($request));
    }

    public function icon(int $iconId)
    {
        $path = $this->titleItems->iconPath($iconId);
        if (!$path) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->validatePayload($request);

        return $this->jsonResult($this->titleItems->create($request));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->validatePayload($request);

        return $this->jsonResult($this->titleItems->update($request, $id));
    }

    private function validatePayload(Request $request): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => 'nullable|integer',
            'gender' => 'nullable|integer|min:0|max:3',
            'effect_data_text' => 'nullable|string|max:400000',
            'effect_data_file' => 'nullable',
            'icon_x1' => 'nullable',
            'icon_x2' => 'nullable',
            'icon_x3' => 'nullable',
            'icon_x4' => 'nullable',
            'effect_x1' => 'nullable',
            'effect_x2' => 'nullable',
            'effect_x3' => 'nullable',
            'effect_x4' => 'nullable',
        ]);
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? (($result['ok'] ?? false) ? 200 : 422));
        unset($result['status']);

        return response()->json($result, $status);
    }
}
