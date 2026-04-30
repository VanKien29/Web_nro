<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\GameCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CatalogLookupController extends Controller
{
    public function __construct(private readonly GameCatalogService $catalog)
    {
    }

    public function searchItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->catalog->searchItems((string) $request->query('q', ''))
        );
    }

    public function options(Request $request): JsonResponse
    {
        return response()->json($this->catalog->optionsForRequest($request)['payload']);
    }
}
