<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private ReportAggregationService $reportService
    ) {}

    /**
     * GET /api/dashboard
     * Returns all KPI data with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'region', 'status']);

        $kpis = $this->reportService->getDashboardKPIs($filters);

        return response()->json([
            'message' => 'Dashboard KPIs retrieved successfully.',
            'data' => $kpis,
        ]);
    }
}
