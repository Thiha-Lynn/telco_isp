<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\FaultReportQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FaultReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $status = $request->input('status');
            
            $query = FaultReportQuery::where('user_id', $user->id)
                ->orderBy('created_at', 'desc');
            
            if ($status) {
                $query->where('status', $status);
            }
            
            $reports = $query->paginate($perPage);
            
            $formattedReports = collect($reports->items())->map(function ($report) {
                return $this->formatFaultReport($report);
            });

            return $this->successResponse([
                'fault_reports' => $formattedReports,
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'last_page' => $reports->lastPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                ]
            ], 'Fault reports retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get fault reports failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve fault reports.');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $report = FaultReportQuery::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$report) {
                return $this->notFoundResponse('Fault report not found.');
            }

            return $this->successResponse([
                'fault_report' => $this->formatFaultReport($report)
            ], 'Fault report details retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get fault report details failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve fault report details.');
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $user = $request->user();
            
            // status: 0 = pending, 1 = in-progress, 2 = resolved, 3 = closed
            $id = DB::table('fault_report_query')->insertGetId([
                'user_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => 0, // pending
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $report = FaultReportQuery::find($id);

            return $this->successResponse([
                'fault_report' => $this->formatFaultReport($report)
            ], 'Fault report submitted successfully', 201);
        } catch (\Exception $e) {
            Log::error('Create fault report failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to submit fault report.');
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'description' => 'string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $user = $request->user();
            
            $report = FaultReportQuery::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$report) {
                return $this->notFoundResponse('Fault report not found.');
            }
            
            if ($report->status !== 0) { // 0 = pending
                return $this->errorResponse('Cannot update a report that is already being processed.', 400);
            }
            
            DB::table('fault_report_query')
                ->where('id', $id)
                ->update([
                    'title' => $request->input('title', $report->title),
                    'description' => $request->input('description', $report->description),
                    'updated_at' => now(),
                ]);

            return $this->successResponse([
                'fault_report' => $this->formatFaultReport(FaultReportQuery::find($id))
            ], 'Fault report updated successfully');
        } catch (\Exception $e) {
            Log::error('Update fault report failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to update fault report.');
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $report = FaultReportQuery::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$report) {
                return $this->notFoundResponse('Fault report not found.');
            }
            
            if ($report->status !== 'pending') {
                return $this->errorResponse('Cannot delete a report that is already being processed.', 400);
            }
            
            $report->delete();

            return $this->successResponse(null, 'Fault report deleted successfully');
        } catch (\Exception $e) {
            Log::error('Delete fault report failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to delete fault report.');
        }
    }

    protected function formatFaultReport($report): array
    {
        return [
            'id' => $report->id,
            'title' => $report->title,
            'description' => $report->description,
            'status' => $report->status,
            'created_at' => $report->created_at ? $report->created_at->toISOString() : null,
            'updated_at' => $report->updated_at ? $report->updated_at->toISOString() : null,
        ];
    }
}
