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
use App\Services\UserLogService;

class UserController extends Controller
{

    protected $userLogService;

    public function __construct(UserLogService $userLogService) {
        $this->userLogService = $userLogService;
    } 
    
    public function showmyprivileges()
    {
        // Get the current user's ID
        $currentUserId = Auth::user()->id;

        // Fetch user privileges
        $myprivileges = DB::table('tbluser')
            ->select(
                'order',
                'unreceived',
                'receiving',
                'labeling',
                'testing',
                'cleaning',
                'packing',
                'stockroom'
            )
            ->where('id', $currentUserId)
            ->first();

        // Return privileges as JSON
        return response()->json([
            'status' => 'success',
            'message' => 'User privileges retrieved successfully',
            'data' => $myprivileges
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string|max:255|unique:tbluser,username',
                'password' => 'required|min:6|confirmed',
                'role' => 'required|in:SuperAdmin,SubAdmin,User',
            ]);
    
            User::create([
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            // Log using service
                    $this->userLogService->log('add user - ' . $validated['username']);
    
            return response()->json([
                'success' => true,
                'message' => 'User added successfully!'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to add user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add user. Please try again.'
            ], 500);
        }
    }
        
    public function updatepassword(Request $request)
        {
            $currentUserId = Auth::user()->id; // Get the current user's ID
    
            // Validate the request
            $request->validate([
                'password' => 'required|min:6|confirmed',
            ]);
    
            try {
                // Find the current user by ID
                $user = User::findOrFail($currentUserId);
    
                // Update the user's password
                $user->update([
                    'password' => Hash::make($request->password),
                ]);

                // Log using service
                        $this->userLogService->log('User Update Password');
    
                return back()->with('success', 'Password updated successfully!');
            } catch (\Exception $e) {
                Log::error('Failed to update password: ' . $e->getMessage());
                return back()->with('error', 'Failed to update password. Please try again.');
            }
        }
    
    public function showStoreColumns()
            {
                $user = new User();
                $storeColumns = $user->getStoreColumns();
    
                return response()->json($storeColumns); // Returns the list of store columns as JSON
            }
    
    public function getStoreColumns()
            {
                // Dynamically fetch columns from the 'tbluser' table
                $columns = Schema::getColumnListing('tbluser');  // Get all columns for the 'tbluser' table
                
                // Filter out only the columns that start with 'store_'
                $storeColumns = array_filter($columns, function ($column) {
                    return str_starts_with($column, 'store_');  // Only return columns with the 'store_' prefix
                });
            
                // Return the store columns as a JSON response
                return response()->json(['stores' => array_values($storeColumns)]);
            }
        
        
    // Controller method to get user privileges
    public function getUserPrivileges($userId)
    {
        $selectedUser = User::find($userId);
        $userPrivileges = null;
    
        if ($selectedUser) {
            // Fetch all columns in the 'tbluser' table starting with 'store_'
            $storeColumns = Schema::getColumnListing('tbluser');
            $storePrivileges = collect($storeColumns)
                ->filter(function ($column) {
                    return str_starts_with($column, 'store_');
                })
                ->map(function ($store) use ($selectedUser) {
                    $storeName = str_replace('store_', '', $store); // Remove 'store_' prefix
                    $storeName = str_replace('_', ' ', $storeName); // Replace underscores with spaces
                    return [
                        'store_column' => $store, // Original column name
                        'store_name' => $storeName, // User-friendly name
                        'is_checked' => (bool) $selectedUser->{$store}, // Check if the privilege is enabled
                    ];
                })
                ->values();
    
            $userPrivileges = [
                'main_module' => $selectedUser->main_module,
                'sub_modules' => [
                    'Order' => (bool) $selectedUser->order,
                    'Unreceived' => (bool) $selectedUser->unreceived,
                    'Receiving' => (bool) $selectedUser->receiving,
                    'Labeling' => (bool) $selectedUser->labeling,
                    'Testing' => (bool) $selectedUser->testing,
                    'Cleaning' => (bool) $selectedUser->cleaning,
                    'Packing' => (bool) $selectedUser->packing,
                    'Stockroom' => (bool) $selectedUser->stockroom,
                ],
                'privileges_stores' => $storePrivileges, // Pass the processed store privileges
            ];
        }
    
        return response()->json($userPrivileges);
    }
    
    public function fetchNewlyAddedStoreCol(Request $request)
    {
        // First, let's log that we've received the request
        Log::info('Starting store fetch process', [
            'user_id' => $request->input('user_id'),
            'request_url' => $request->fullUrl()
        ]);
    
        try {
            $stores = []; 
            $userId = $request->input('user_id');
    
            // Let's verify we can connect to the database
            try {
                DB::connection()->getPdo();
                Log::info('Database connection successful');
            } catch (\Exception $e) {
                Log::error('Database connection failed', ['error' => $e->getMessage()]);
                throw new \Exception('Database connection failed: ' . $e->getMessage());
            }
    
            // Check if we can access the schema
            try {
                $storeColumns = Schema::getColumnListing('tbluser');
                Log::info('Successfully retrieved columns', ['columns' => $storeColumns]);
            } catch (\Exception $e) {
                Log::error('Failed to get table columns', ['error' => $e->getMessage()]);
                throw new \Exception('Schema access failed: ' . $e->getMessage());
            }
    
            // Verify user exists if user_id is provided
            if ($userId) {
                $user = User::find($userId);
                if (!$user) {
                    Log::warning('User not found', ['user_id' => $userId]);
                    return response()->json(['error' => 'User not found'], 404);
                }
                Log::info('User found', ['user_id' => $userId]);
            }
    
            // Process the store columns
            $stores = collect($storeColumns)
                ->filter(function ($column) {
                    return str_starts_with($column, 'store_');
                })
                ->map(function ($store) use ($userId, $user) {
                    Log::info('Processing store column', ['store' => $store]);
                    
                    $storeName = str_replace('store_', '', $store);
                    $storeName = str_replace('_', ' ', $storeName);
    
                    // Safely check privileges
                    try {
                        $isChecked = false;
                        if ($userId && isset($user) && method_exists($user, 'privileges_stores')) {
                            $isChecked = $user->privileges_stores->contains($store);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error checking store privileges', [
                            'store' => $store,
                            'error' => $e->getMessage()
                        ]);
                        throw new \Exception('Privilege check failed: ' . $e->getMessage());
                    }
    
                    return [
                        'store_column' => $store,
                        'store_name' => $storeName,
                        'is_checked' => $isChecked,
                    ];
                })
                ->values();
    
            Log::info('Successfully processed stores', ['store_count' => count($stores)]);
            return response()->json(['stores' => $stores]);
    
        } catch (\Exception $e) {
            Log::error('Store fetching failed', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch stores',
                'message' => $e->getMessage()
            ], 500);
        }
    }

public function saveUserPrivileges(Request $request)
        {
            try {
                // Typecast user_id to integer before validation
                $request->merge(['user_id' => (int) $request->input('user_id')]);
        
                // Validate the request
                $data = $request->validate([
                    'user_id' => 'required|numeric|exists:tbluser,id',
                    'main_module' => 'required|string',
                    'sub_modules' => 'array|nullable',
                    'privileges_stores' => 'array|nullable',
                ]);
        
                // Log the request data for debugging
                Log::info('Request Data:', $data);
        
                // Fetch the user
                $user = User::find($data['user_id']);
                $username = $user->username; // Store username for logging

                if (!$user) {
                    return response()->json(['success' => false, 'message' => 'User not found']);
                }
                Log::info('Fetched User:', ['user' => $user]);
        
                // Update main module
                $user->main_module = $data['main_module'];
        
                // Update sub-modules dynamically
                $subModules = ['order', 'unreceived', 'receiving', 'labeling', 'testing', 'cleaning', 'packing', 'stockroom']; // Ensure these modules are valid
                foreach ($subModules as $module) {
                    $user->{$module} = in_array(ucfirst($module), $data['sub_modules'] ?? []) ? 1 : 0;
                }
        
                // Fetch all store columns dynamically
                $storeColumns = DB::select("SHOW COLUMNS FROM tbluser LIKE 'store_%'");
                $storeColumns = array_map(fn($column) => $column->Field, $storeColumns);
        
                // Log store columns
                Log::info('Store Columns:', $storeColumns);
        
                // Reset all store columns to 0
                foreach ($storeColumns as $storeColumn) {
                    $user->{$storeColumn} = 0;
                }
        
                // Enable selected stores
                if (!empty($data['privileges_stores'])) {
                    foreach ($data['privileges_stores'] as $store) {
                        if (in_array($store, $storeColumns)) {
                            $user->{$store} = 1;
                        } else {
                            Log::warning("Store column '{$store}' does not exist in tbluser.");
                        }
                    }
                }
        
                // Collect different types of modules
                $mainModule = $data['main_module'];
                $enabledSubModules = [];
                $enabledStores = [];

                // Collect enabled sub-modules
                foreach ($subModules as $module) {
                    if ($user->{$module} == 1) {
                        $enabledSubModules[] = ucfirst($module);
                    }
                }

                // Collect enabled stores
                foreach ($storeColumns as $storeColumn) {
                    if ($user->{$storeColumn} == 1) {
                        $storeName = str_replace('store_', '', $storeColumn);
                        $storeName = str_replace('_', ' ', $storeName);
                        $enabledStores[] = ucfirst($storeName);
                    }
                }

                // Format the log message
                $logMessage = sprintf(
                    'Update Privileges for User %s - Main: %s | Sub-Modules: %s | Stores: %s',
                    $username,
                    $mainModule,
                    $enabledSubModules ? implode(', ', $enabledSubModules) : 'None',
                    $enabledStores ? implode(', ', $enabledStores) : 'None'
                );

                // Save the user privileges
                $user->save();

                // Log using service
                $this->userLogService->log($logMessage);
        
                return response()->json(['success' => true, 'message' => 'User privileges updated successfully!']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Validation Error:', ['errors' => $e->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ]);
            } catch (\Exception $e) {
                Log::error('Error saving user privileges:', ['error' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => $e->getMessage()]);
            }
        }        

        
    public function createdusers()
        {
            $user = User::select('id', 'username', 'role', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();
        
            // Return privileges as JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => $user
        ]);
        }

    public function update(Request $request, $id)
        {
            $request->validate([
                'username' => 'required|string|max:255|unique:tbluser,username,'.$id,
                'password' => 'nullable|min:6',
                'role' => 'required|in:SuperAdmin,SubAdmin,User',
            ]);
        
            try {
                $user = User::findOrFail($id);
                
                $updateData = [
                    'username' => $request->username,
                    'role' => $request->role,
                ];
        
                if ($request->filled('password')) {
                    $updateData['password'] = Hash::make($request->password);
                }
        
                $user->update($updateData);

                // Log using service
                        $this->userLogService->log('Update data of User - ' . $request->username);
        
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully!'
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to update user: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user. Please try again.'
                ]);
            }
        }
        
        public function destroy($id)
        {
            try {
                // Get the user and username before deleting
                $user = User::findOrFail($id);
                $username = $user->username; // Store username for logging
        
                // Delete the user
                $user->delete();
        
                // Log using service
                $this->userLogService->log('Deleted User - ' . $username);
        
                return response()->json([
                    'success' => true,
                    'message' => 'User deleted successfully!'
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to delete user: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete user. Please try again.'
                ]);
            }
        }
}    