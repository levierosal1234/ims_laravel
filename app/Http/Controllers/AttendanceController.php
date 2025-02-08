<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function attendance()
    {
        // Get the current user's ID from the session or Auth
        $currentUserId = Auth::user()->id;

        // Query the attendance data for the logged-in user, ordered by TimeIn
        $employeeClocks = DB::table('tblemployeeclocks')
        ->join('tbluser', 'tblemployeeclocks.userid', '=', 'tbluser.id')
        ->select(
            'tblemployeeclocks.ID as clock_id', // Alias for ID
            'tblemployeeclocks.userid as user_id', // Alias for userid
            'tblemployeeclocks.Employee as employee_name', // Alias for Employee
            'tblemployeeclocks.TimeIn as time_in', // Alias for TimeIn
            'tblemployeeclocks.TimeOut as time_out', // Alias for TimeOut
            'tbluser.username as user_name',
            'tblemployeeclocks.Notes as notes_' 
        )
        ->where('tblemployeeclocks.userid', $currentUserId) // Filter by the current user's ID
        ->orderBy('tblemployeeclocks.TimeIn', 'desc') // Order by TimeIn (descending)
        ->get();


            // Query the attendance data for the logged-in user, where TimeIn is in the current week
        $employeeClocksThisweek = DB::table('tblemployeeclocks')
        ->join('tbluser', 'tblemployeeclocks.userid', '=', 'tbluser.id')
        ->select(
            'tblemployeeclocks.ID as ID',
            'tblemployeeclocks.userid',
            'tblemployeeclocks.Employee',
            'TimeIn',
            'TimeOut',
            'Notes',
            'tbluser.username'
        )
        ->where('tblemployeeclocks.userid', $currentUserId) // Filter by the current user's ID
        ->whereBetween('tblemployeeclocks.TimeIn', [
            Carbon::now('America/Los_Angeles')->startOfWeek(),
            Carbon::now('America/Los_Angeles')->endOfWeek(),
        ]) // Filter records where TimeIn is this week
        ->orderBy('tblemployeeclocks.TimeIn', 'desc') // Order by TimeIn (descending)
        ->get();

        // Fetch the most recent clock-in record for today with no clock-out
        $lastRecord = DB::table('tblemployeeclocks')
            ->where('userid', $currentUserId)
            ->whereDate('TimeIn', Carbon::today('America/Los_Angeles')) // Check if TimeIn is today
            ->orderBy('ID', 'desc') // Get the most recent record
            ->first(); // Retrieve only the last record

        $verylastRecord = DB::table('tblemployeeclocks')
            ->where('userid', $currentUserId)
            ->orderBy('ID', 'desc') // Get the most recent record
            ->first(); // Retrieve the most recent record

        // Calculate Today's Hours
        $todayHours = DB::table('tblemployeeclocks')
        ->where('userid', $currentUserId)
        ->whereDate('TimeIn', Carbon::today('America/Los_Angeles'))
        ->sum(DB::raw("
            TIMESTAMPDIFF(
                MINUTE,
                TimeIn,
                COALESCE(TimeOut, DATE_SUB(NOW(), INTERVAL 8 HOUR))
            )
        "));

        // Calculate This Week's Hours
        $weekHours = DB::table('tblemployeeclocks')
            ->where('userid', $currentUserId)
            ->whereBetween('TimeIn', [
                Carbon::now('America/Los_Angeles')->startOfWeek(),
                Carbon::now('America/Los_Angeles')->endOfWeek(),
            ])
            ->sum(DB::raw("
                TIMESTAMPDIFF(
                    MINUTE,
                    TimeIn,
                    COALESCE(TimeOut, DATE_SUB(NOW(), INTERVAL 8 HOUR))
                )
            "));


        // Format hours as H:mm
        $todayHoursFormatted = sprintf('%d hrs %02d mins', intdiv($todayHours, 60), $todayHours % 60);
        $weekHoursFormatted = sprintf('%d hrs %02d mins', intdiv($weekHours, 60), $weekHours % 60);

        // Pass the data to the Blade view
        return view('dashboard.Systemdashboard', 
            compact('employeeClocks', 'lastRecord', 'verylastRecord', 'todayHoursFormatted', 'weekHoursFormatted', 'employeeClocksThisweek'));
    }

    public function clockIn(Request $request)
    {
        // Get the current user's ID
        $currentUserId = Auth::user()->id;
        $currentUsername = Auth::user()->username;

        // Get the current date and time
        $currentDateTime = Carbon::now('America/Los_Angeles');

        // Insert into the tblemployeeclocks table
        DB::table('tblemployeeclocks')->insert([
            'userid' => $currentUserId,
            'Employee' => $currentUsername,
            'TimeIn' => $currentDateTime,
        ]);

        // Redirect back with a success message
        return redirect()->back()->with('success_clockin', 'Clocked in successfully at ' . $currentDateTime->format('h:i A'));
    }

    public function clockOut(Request $request)
    {
        // Get the current user's ID
        $currentUserId = Auth::user()->id;

        // Get the current date and time
        $currentDateTime = Carbon::now('America/Los_Angeles');

        // Get the last record for the current user with today's TimeIn and null TimeOut
        $lastRecord = DB::table('tblemployeeclocks')
            ->where('userid', $currentUserId)
            ->whereDate('TimeIn', Carbon::today('America/Los_Angeles')) // Ensure TimeIn is today's date
            ->whereNotNull('TimeIn') // Ensure TimeIn is not null
            ->whereNull('TimeOut') // Ensure TimeOut is null (no clock-out yet)
            ->orderBy('ID', 'desc') // Get the most recent record
            ->first(); // Retrieve only the last record

        if ($lastRecord) {
            // Update the TimeOut field for the last record
            DB::table('tblemployeeclocks')
                ->where('ID', $lastRecord->ID) // Update only the last record by ID
                ->update(['TimeOut' => $currentDateTime]);

            // Redirect back with a success message
            return redirect()->back()->with('success_clockout', 'Clocked out successfully at ' . $currentDateTime->format('h:i A'));
        } else {
            // If no valid record found, return an error message
            return redirect()->back()->with('error', 'No valid clock-in record found for today to clock out.');
        }
    }

    public function autoClockOut(Request $request)
    {
        // Get the current user's ID
        $currentUserId = Auth::user()->id;
    
        // Get the last record for the current user with null TimeOut
        $lastRecord = DB::table('tblemployeeclocks')
            ->where('userid', $currentUserId)
            ->whereNotNull('TimeIn') // Ensure TimeIn is not null
            ->whereNull('TimeOut') // Ensure TimeOut is null (no clock-out yet)
            ->orderBy('ID', 'desc') // Get the most recent record
            ->first(); // Retrieve only the last record
    
        if ($lastRecord) {
            // Update the TimeOut field to match the TimeIn field
            DB::table('tblemployeeclocks')
                ->where('ID', $lastRecord->ID) // Update only the last record by ID
                ->update([
                    'TimeOut' => $lastRecord->TimeIn,
                    'Notes' => 'System Automatically Clocked out with TimeOut matching TimeIn at ' . $lastRecord->TimeIn,
                ]);
    
            // Return success response as JSON
            return response()->json([
                'success' => true,
                'message' => 'System Automatically Clocked out with TimeOut matching TimeIn at ' . $lastRecord->TimeIn,
            ]);
        } else {
            // Return error response as JSON
            return response()->json([
                'success' => false,
                'message' => 'No valid clock-in record found to clock out.',
            ]);
        }
    }
    
    public function updateComputedHours(Request $request)
    {
        // Parse TimeIn and TimeOut values
        $timeIn = Carbon::parse($request->timeIn)->setTimezone('America/Los_Angeles');
        $timeOut = $request->timeOut 
            ? Carbon::parse($request->timeOut)->setTimezone('America/Los_Angeles')
            : now()->setTimezone('America/Los_Angeles')->subHours(8);

        // Calculate total hours and minutes worked
        $totalMinutes = $timeIn->diffInMinutes($timeOut);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        // Return as JSON
        return response()->json([
            'hours' => $hours,
            'minutes' => $minutes,
            'message' => !$request->timeOut ? 'Calculated until now' : null,
        ]);
    }

    public function updateHours()
    {
        // Get the current user's ID
        $currentUserId = Auth::user()->id;

        // Calculate Today's Hours
        $todayHours = DB::table('tblemployeeclocks')
            ->where('userid', $currentUserId)
            ->whereDate('TimeIn', Carbon::today('America/Los_Angeles'))
            ->sum(DB::raw("
                TIMESTAMPDIFF(
                    MINUTE,
                    TimeIn,
                    COALESCE(TimeOut, DATE_SUB(NOW(), INTERVAL 8 HOUR))
                )
            "));

        // Calculate This Week's Hours
        $weekHours = DB::table('tblemployeeclocks')
            ->where('userid', $currentUserId)
            ->whereBetween('TimeIn', [
                Carbon::now('America/Los_Angeles')->startOfWeek(),
                Carbon::now('America/Los_Angeles')->endOfWeek(),
            ])
            ->sum(DB::raw("
                TIMESTAMPDIFF(
                    MINUTE,
                    TimeIn,
                    COALESCE(TimeOut, DATE_SUB(NOW(), INTERVAL 8 HOUR))
                )
            "));

        // Format hours as H:mm
        $todayHoursFormatted = sprintf('%d hrs %02d mins', intdiv($todayHours, 60), $todayHours % 60);
        $weekHoursFormatted = sprintf('%d hrs %02d mins', intdiv($weekHours, 60), $weekHours % 60);

        // Return as JSON
        return response()->json([
            'todayHours' => $todayHoursFormatted,
            'weekHours' => $weekHoursFormatted,
        ]);
    }

    public function filterAttendanceAjax(Request $request)
    {
        $currentUserId = Auth::user()->id;

        // Get date range or default to null
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = DB::table('tblemployeeclocks')
            ->join('tbluser', 'tblemployeeclocks.userid', '=', 'tbluser.id')
            ->select(
                'tblemployeeclocks.ID as clock_id',
                'tblemployeeclocks.userid as user_id',
                'tblemployeeclocks.Employee as employee_name',
                'tblemployeeclocks.TimeIn as time_in',
                'tblemployeeclocks.TimeOut as time_out',
                'tbluser.username as user_name'
            )
            ->where('tblemployeeclocks.userid', $currentUserId)
            ->orderBy('tblemployeeclocks.TimeIn', 'desc');

        // Apply date range if provided
        if ($startDate) {
            $query->whereDate('tblemployeeclocks.TimeIn', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('tblemployeeclocks.TimeIn', '<=', $endDate);
        }

        // Default to limit 10 rows if no range is provided
        $employeeClocks = $query->limit(10)->get();

        return response()->json([
            'employeeClocks' => $employeeClocks,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function updateNotes(Request $request, $id)
    {
        $validatedData = $request->validate([
            'notes' => 'required|string|max:255',
        ]);
    
        $updated = DB::table('tblemployeeclocks')
            ->where('ID', $id)
            ->update(['Notes' => $validatedData['notes']]);
    
        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'Notes updated successfully.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notes.',
            ]);
        }
    }

    

}
