<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->dashboard->stats());
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json($this->dashboard->history($request));
    }

    public function revenue(Request $request): JsonResponse
    {
        return response()->json($this->dashboard->revenue($request));
    }

    public function topUsers(): JsonResponse
    {
        return response()->json($this->dashboard->topUsers());
    }

    public function monthlyRevenue(Request $request): JsonResponse
    {
        return response()->json($this->dashboard->monthlyRevenue($request));
    }

    public function overview(): JsonResponse
    {
        return response()->json($this->dashboard->overview());
    }
}
