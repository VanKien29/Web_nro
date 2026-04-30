<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\GameCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ItemController extends Controller
{
    public function __construct(private readonly GameCatalogService $catalog)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->catalog->listItems($request));
    }

    public function batch(Request $request): JsonResponse
    {
        return response()->json($this->catalog->batchItems((string) $request->query('ids', '')));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|integer',
            'gender' => 'nullable|integer|min:0|max:3',
            'icon_id' => 'required|integer|min:0',
            'part' => 'nullable|integer|min:-1',
            'head' => 'nullable|integer|min:-1',
            'body' => 'nullable|integer|min:-1',
            'leg' => 'nullable|integer|min:-1',
            'description' => 'nullable|string|max:2000',
            'is_up_to_up' => 'nullable|boolean',
        ]);

        $result = $this->catalog->updateItem($request, $id);

        if (($result['ok'] ?? false) !== true) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?? 'Không thể cập nhật item',
            ], $result['status'] ?? 422);
        }

        return response()->json($result);
    }

    public function options(int $id): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'options' => $this->catalog->itemOptions(),
        ]);
    }
}
