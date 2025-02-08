<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserSessionController extends Controller
{
    public function checkUserPrivileges()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }
    
            // Get fresh user data with no cache
            $user = User::find($user->id)->fresh();
    
            // Get main module
            $mainModule = $user->main_module;
    
            // Get enabled modules by checking database columns
            $subModules = [];
    
            // Directly check the boolean columns
            if ($user->order) $subModules[] = 'order';
            if ($user->unreceived) $subModules[] = 'unreceived';
            if ($user->receiving) $subModules[] = 'receiving';
            if ($user->labeling) $subModules[] = 'labeling';
            if ($user->testing) $subModules[] = 'testing';
            if ($user->cleaning) $subModules[] = 'cleaning';
            if ($user->packing) $subModules[] = 'packing';
            if ($user->stockroom) $subModules[] = 'stockroom';
    
            // Update session with fresh data
            Session::forget('main_module');
            Session::forget('sub_modules');
            Session::put('main_module', strtolower($mainModule));
            Session::put('sub_modules', array_map('strtolower', $subModules));
            Session::save();
    
            // Debugging log
            Log::info('Updated user privileges and session', [
                'user_id' => $user->id,
                'main_module' => $mainModule,
                'sub_modules' => $subModules,
                'session_data' => [
                    'main_module' => Session::get('main_module'),
                    'sub_modules' => Session::get('sub_modules')
                ]
            ]);
    
            return response()->json([
                'success' => true,
                'main_module' => strtolower($mainModule),
                'sub_modules' => array_map('strtolower', $subModules),
                'modules' => [
                    'order' => 'Order',
                    'unreceived' => 'Unreceived',
                    'receiving' => 'Receiving',
                    'labeling' => 'Labeling',
                    'testing' => 'Testing',
                    'cleaning' => 'Cleaning',
                    'packing' => 'Packing',
                    'stockroom' => 'Stockroom'
                ]
            ]);
    
        } catch (\Exception $e) {
            Log::error('Error checking privileges', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
    
            return response()->json([
                'error' => 'Failed to check privileges',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function refreshSession(Request $request)
{
    try {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        // Force session regenerate
        $request->session()->regenerate();
        
        // Re-fetch privileges
        return $this->checkUserPrivileges();
    } catch (\Exception $e) {
        Log::error('Error refreshing session', [
            'error' => $e->getMessage(),
            'user_id' => Auth::id()
        ]);

        return response()->json([
            'error' => 'Failed to refresh session',
            'message' => $e->getMessage()
        ], 500);
    }
}
}