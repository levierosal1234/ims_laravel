<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\SystemDesign;
use Illuminate\Support\Facades\DB;
use App\Services\UserLogService;
use Carbon\Carbon; // Make sure this is imported

class LoginController extends Controller
{

    protected $userLogService;

    public function __construct(UserLogService $userLogService) {
        $this->userLogService = $userLogService;
    }

    public function showLoginForm()
    {
        $systemDesign = SystemDesign::first();
        
        // Store system design settings in session
        if ($systemDesign) {
            session([
                'site_title' => $systemDesign->site_title,
                'theme_color' => $systemDesign->theme_color,
                'logo' => $systemDesign->logo,
            ]);
        }

        return view('login.index', compact('systemDesign'));
    }

    public function authenticate(Request $request)
    {
        // Validate login credentials
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Attempt authentication
        if (Auth::attempt($credentials)) {
            // Regenerate session for security
            $request->session()->regenerate();
            
            // Get the authenticated user
            $user = Auth::user();
            
            // Store basic user information
            $this->storeUserSession($user, $request);
            
            // Store system design settings
            $this->storeSystemDesign($request);
            
            // Store module permissions
            $this->storeModulePermissions($user, $request);
            
            // Store store permissions
            $this->storeStorePermissions($user, $request);

            // Log using service
            $this->userLogService->log('User LOGIN');

            return redirect()->back()->with('success', 'Log in successfully');
        }
        
        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->withInput();
    }

    private function storeUserSession($user, $request)
    {
        $request->session()->put([
            'user_name' => $user->username,
            'profile_picture' => $user->profile_picture,
            'userid' => $user->id
        ]);
    }

    private function storeSystemDesign($request)
    {
        $systemDesign = SystemDesign::first();
        if ($systemDesign) {
            $request->session()->put([
                'site_title' => $systemDesign->site_title,
                'theme_color' => $systemDesign->theme_color,
                'logo' => $systemDesign->logo
            ]);
        }
    }

    private function storeModulePermissions($user, $request)
    {
        // Store main module - this is the key change you needed
        $mainModule = $user->main_module;
        if (!empty($mainModule)) {
            $request->session()->put('main_module', $mainModule);
        }

        // Store sub-modules
        $subModules = ['order', 'unreceived', 'receiving', 'labeling', 
                      'testing', 'cleaning', 'packing', 'stockroom'];
        
        $activeSubModules = array_filter($subModules, function($module) use ($user) {
            return $user->{$module} == 1;
        });

        $request->session()->put('sub_modules', array_values($activeSubModules));
    }

    private function storeStorePermissions($user, $request)
    {
        // Get store columns from database
        $storeColumns = DB::select("SHOW COLUMNS FROM tbluser LIKE 'store_%'");
        
        // Filter active stores
        $activeStores = array_filter(
            array_map(fn($column) => $column->Field, $storeColumns),
            fn($store) => $user->{$store} == 1
        );

        $request->session()->put('stores', array_values($activeStores));
    }

    public function showSystemDashboard()
    {
        if (Auth::check()) {
            $Allusers = \App\Models\User::all();
            return view('dashboard.Systemdashboard', ['Allusers' => $Allusers]);
        }
        
        return redirect()->route('login')
            ->with('error', 'Please log in to access the dashboard.');
    }
}