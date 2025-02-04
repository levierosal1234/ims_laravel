<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SystemDesignController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AwsInventoryController;
use App\Http\Controllers\StoreController;
use App\Http\Models\Store;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserPrivilegesController;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\USPSController;
use App\Http\Controllers\UPSController;
use App\Http\Controllers\UserSessionController;


Route::get('/', function () {
    return view('welcome');
});

// Logout Route
Route::post('/logout', function () {
    // Clear all session data
    Session::flush();
    
    // Logout the user
    Auth::logout();
    
    // Invalidate the session
    request()->session()->invalidate();
    
    // Regenerate the CSRF token
    request()->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

// Login Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login');

// Dashboard Route (Protected with auth middleware)
Route::get('/dashboard/Systemdashboard', [LoginController::class, 'showSystemDashboard'])->middleware('auth');
Route::get('/get-user-privileges/{userId}', [UserController::class, 'getUserPrivileges']);
Route::post('/save-user-privileges', [UserController::class, 'saveUserPrivileges'])->name('saveUserPrivileges');


Route::get('/fetchNewlyAddedStoreCol', [UserController::class, 'fetchNewlyAddedStoreCol']);


Route::get('/get-store-columns', [UserController::class, 'getStoreColumns']);

Route::get('/dashboard/Systemdashboard', function () {
    return view('dashboard.Systemdashboard');
})->middleware('auth');

// User Routes
Route::post('/add-user', [UserController::class, 'store'])->name('add-user');
Route::post('/update-password', [UserController::class, 'updatepassword'])->name('update-password');
Route::get('/myprivileges', [UserController::class, 'showmyprivileges'])->name('myprivileges');
Route::get('/users', [UserController::class, 'createdusers'])->name('user');
Route::post('/update-user/{id}', [UserController::class, 'update'])->name('update-user');
Route::delete('/delete-user/{id}', [UserController::class, 'destroy'])->name('delete-user');

// System Design Routes
Route::post('/update-system-design', [SystemDesignController::class, 'update'])->name('update.system.design');



// Module Routes
Route::get('/Systemmodule/{module}Module/{moduleName}', function ($module, $moduleName) {
    $availableModules = ['Order', 'Unreceived', 'Receiving', 'Labeling', 'Validation', 'Testing', 'Cleaning', 'Packing', 'Stockroom'];

    if (in_array($moduleName, $availableModules)) {
        return view("Systemmodule.{$module}Module.{$moduleName}");
    }

    abort(404);
})->name('modules');


Route::get('/get-stores', [StoreController::class, 'getStores']);
Route::get('/get-store/{id}', [StoreController::class, 'getStoreID'])->name('get-store');
Route::post('/update-store/{id}', [StoreController::class, 'updateStore'])->name('update-store');

Route::post('/add-store', [StoreController::class, 'addstore'])->name('add-store');
Route::delete('/delete-store/{id}', [StoreController::class, 'delete'])->name('delete-store');
Route::get('/fetch-marketplaces', [StoreController::class, 'fetchMarketplaces']);
Route::get('/fetch-marketplaces-tblstores', [StoreController::class, 'fetchMarketplacestblstores'])->name('fetchMarketplacestblstores');
Route::post('/user/privileges/update', [UserPrivilegesController::class, 'update'])->name('update-user-privileges');

Route::get('/dashboard/Systemdashboard', [AttendanceController::class, 'attendance']);
Route::post('/attendance/clockin', [AttendanceController::class, 'clockIn'])->name('attendance.clockin');
Route::post('/attendance/clockout', [AttendanceController::class, 'clockOut'])->name('attendance.clockout');
Route::post('/update-computed-hours', [AttendanceController::class, 'updateComputedHours'])->name('update.computed.hours');
Route::post('/attendance/update-hours', [AttendanceController::class, 'updateHours'])->name('attendance.update.hours');
Route::post('/attendance/filter', [AttendanceController::class, 'filterAttendanceAjax'])->name('attendance.filter.ajax');
Route::post('/attendance/auto-clockout', [AttendanceController::class, 'autoClockOut'])->name('auto-clockout');
Route::post('/update-notes/{id}', [AttendanceController::class, 'updateNotes'])->name('update-notes');


// AWS Inventory Routes
Route::get('/aws-inventory', function () {
    return view('tests.aws_inventory');
})->name('aws.inventory.view');
Route::post('/aws/inventory/summary', [AwsInventoryController::class, 'fetchInventorySummary'])->name('aws.inventory.summary');

// USPS
Route::get('/uspstracking', function () {
    return view('tests.usps');
})->name('usps.tracking');

Route::post('/usps/tracking', [USPSController::class, 'USPSfetchTrackDetails'])->name('usps.trackingnumber');

// UPS
Route::get('/apis/upstracking', function () {
    return view('tests.ups');
})->name('ups.tracking');

Route::post('/apis/upstracking', [UPSController::class, 'UPSfetchTrackDetails'])->name('UPS.trackingnumber');

Route::get('/apis/ebay-callback', action: function () {
    require app_path('Helpers/ebay_helpers.php');
    // Check if the 'code' parameter is present in the URL
    echo "Hello";
    if (isset($_GET['code'])) {
        $authorizationCode = $_GET['code']; // Get the authorization code from the URL
        // Call the getAccessToken function to exchange the authorization code for an access token
        $accessToken = getAccessToken($authorizationCode);

        if ($accessToken) {
            // Access token obtained successfully
            return response()->json(['access_token' => $accessToken]);
        } else {
            // Failed to retrieve access token
            return response()->json(['error' => 'Unable to obtain access token.'], 500);
        }
    } else {
        // No authorization code received in the request
        return response()->json(['error' => 'Authorization code not provided.'], 400);
    }
});

Route::get('/apis/ebay-login', action: function () {
    $clientId = 'LevieRos-imsweb-PRD-7abfbb41d-7a45e67e'; // Replace with your client ID
    $redirectUrl = 'https://ims.tecniquality.com/Admin/modules/orders/callback.php'; // Replace with your redirect URL
    $scopes = 'https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly';
    
    $authUrl = "https://auth.ebay.com/oauth2/authorize?client_id={$clientId}&redirect_uri={$redirectUrl}&response_type=code&scope=" . urlencode($scopes);
    
    echo "<a href='{$authUrl}'>Authorize with eBay</a>";
});


Route::get('/apis/ebay-callback', action: function () {
    require app_path('Helpers/ebay_helpers.php');
    // Check if the 'code' parameter is present in the URL
    echo "Hello";
    if (isset($_GET['code'])) {
        $authorizationCode = $_GET['code']; // Get the authorization code from the URL
        // Call the getAccessToken function to exchange the authorization code for an access token
        $accessToken = getAccessToken($authorizationCode);

        if ($accessToken) {
            // Access token obtained successfully
            return response()->json(['access_token' => $accessToken]);
        } else {
            // Failed to retrieve access token
            return response()->json(['error' => 'Unable to obtain access token.'], 500);
        }
    } else {
        // No authorization code received in the request
        return response()->json(['error' => 'Authorization code not provided.'], 400);
    }
});

Route::get('/apis/ebay-login', action: function () {
    $clientId = 'LevieRos-imsweb-PRD-7abfbb41d-7a45e67e'; // Replace with your client ID
    $redirectUrl = 'https://ims.tecniquality.com/Admin/modules/orders/callback.php'; // Replace with your redirect URL
    $scopes = 'https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly';
    
    $authUrl = "https://auth.ebay.com/oauth2/authorize?client_id={$clientId}&redirect_uri={$redirectUrl}&response_type=code&scope=" . urlencode($scopes);
    
    echo "<a href='{$authUrl}'>Authorize with eBay</a>";
});


use App\Http\Controllers\TestTableController;

Route::get('/test', [TestTableController::class, 'index']);


Route::get('/check-user-privileges', [UserSessionController::class, 'checkUserPrivileges'])->middleware('auth');

// In routes/web.php
Route::post('/refresh-user-session', [UserSessionController::class, 'refreshSession']);