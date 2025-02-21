<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon; // Make sure this is imported

class UserLogsController extends Controller 
{
    public function getUserLogs(Request $request)
    {
        $query = DB::table('tbluserlogs')
            ->select('username', 'actions', 'datetimelogs');

        // Add filters if provided
        if ($request->has('user_id')) {
            $query->where('userid', $request->user_id);
        }

        // Fix date filtering
        if ($request->has('start_date_logs') && $request->start_date_logs) {
            $startDate = date('Y-m-d 00:00:00', strtotime($request->start_date_logs));
            $query->where('datetimelogs', '>=', $startDate);
        }

        if ($request->has('end_date_logs') && $request->end_date_logs) {
            $endDate = date('Y-m-d 23:59:59', strtotime($request->end_date_logs));
            $query->where('datetimelogs', '<=', $endDate);
        }

        $records = $query->orderBy('datetimelogs', 'desc')->get();
        return response()->json($records);
    }
}