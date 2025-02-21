<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UserLogService
{
    public function log($action)
    {
        $currentUserId = Auth::user()->id;
        $currentUsername = Auth::user()->username;
        $currentDateTime = Carbon::now('America/Los_Angeles');

        return DB::table('tbluserlogs')->insert([
            'userid' => $currentUserId,
            'username' => $currentUsername,
            'actions' => $action . ' at ' . $currentDateTime->format('h:i A'),
            'datetimelogs' => $currentDateTime,
        ]);
    }
}