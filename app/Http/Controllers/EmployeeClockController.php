<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon; // Make sure this is imported

class EmployeeClockController extends Controller
{

    public function getUserTimeRecords(Request $request, $userId)
    {
        $query = DB::table('tblemployeeclocks')
            ->where('userid', $userId);

        if ($request->has('start_date') && $request->start_date) {
            $query->where('TimeIn', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('TimeIn', '<=', $request->end_date . ' 23:59:59');
        }

        $records = $query->orderBy('TimeIn', 'desc')->get();

        return response()->json($records);
    }

}