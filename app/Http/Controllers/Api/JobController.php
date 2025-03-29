<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\JobFilterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JobController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        try {
            $filter = $request->query('filter');
            if ($filter && !is_string($filter)) {
                throw new \InvalidArgumentException('Filter parameter must be a string');
            }

            $query = Job::query()
                ->with(['languages', 'locations', 'categories', 'attributeValues.attribute']);

            $filterService = new JobFilterService(
                $query,
                $filter,
                $request->query('sort')
            );

            $jobs = $filterService->apply()->paginate(20);

            return response()->json([
                'data' => $jobs->items(),
                'meta' => [
                    'current_page' => $jobs->currentPage(),
                    'total' => $jobs->total(),
                    'per_page' => $jobs->perPage(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Invalid filter or sort parameter',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }

    }
}
