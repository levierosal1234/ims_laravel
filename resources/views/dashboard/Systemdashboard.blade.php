
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ session('site_title', 'IMS') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
        }

        .navbar {
            background-color: {{ session('theme_color', '#007bff') }};
            transition: margin-left 0.3s ease-in-out, padding-left 0.3s ease-in-out;
        }

        .navbar-brand, .nav-link {
            color: #fff !important;
        }

  

        .sidebar {
            height: 100vh;
            background: #343a40;
            padding: 1rem;
            color: #fff;
            position: fixed;
            top: 0;
            left: -240px; /* Start with sidebar hidden */
            width: 240px;
            transition: left 0.3s ease-in-out;
            z-index: 1050;
        }

        .sidebar.visible {
            left: 0; /* Show the sidebar */
        }

        .sidebar .nav-link {
            color: #adb5bd;
            position: relative;
            z-index: 1; /* Ensure it's clickable */
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #495057;
            border-radius: 5px;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #fff;
            cursor: pointer;
        }

        .content {
            margin-left: 0;
            padding: 2rem;
            transition: margin-left 0.3s ease-in-out;
        }

        .content.sidebar-visible {
            margin-left: 240px;
        }

        #burger-menu {
            display: block;
        }

        #burger-menu.hidden {
            display: none; /* Hide burger menu when sidebar is visible */
        }

        .navbar-brand.shifted {
            margin-left: 240px; /* Shift logo/name when sidebar is visible */
            transition: margin-left 0.3s ease-in-out;
        }

        footer {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                left: -100%; /* Fully hide sidebar in mobile view */
            }

            .sidebar.visible {
                left: 0; /* Show the sidebar in full screen */
            }

            .content {
                margin-left: 0 !important; /* Prevent shifting of content */
            }

            .navbar-brand.shifted {
                margin-left: 0 !important; /* Reset navbar logo position */
            }

            #burger-menu {
                display: block; /* Ensure burger menu is always visible */
            }
        }

        #top-search {
            display: none;  /* Hide by default */
            align-items: center;
            width: 100%;
            max-width: 600px; /* Adjust max width as needed */
            margin: 0 auto;
        }

        #top-search.show {
            display: flex;  /* Only display when the 'show' class is added */
        }

        #search-input {
            width: 100%;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
            /* Ensure tab text is visible */
    .nav-tabs .nav-link {
        color: black !important; /* Set text color to black */
        font-weight: bold;      /* Make it stand out */
    }
    .nav-tabs .nav-link.active {
        color: white !important; /* Set text color to white for the active tab */
        background-color: #007bff !important; /* Blue background for active tab */
    }

          #storeList .list-group-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    margin-bottom: 5px;
    border-radius: 5px;
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

#storeList .list-group-item:hover {
    background-color: #e9ecef;
}

#storeList .edit-store-btn {
    background-color: #007bff;
    border: none;
    color: white;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 3px;
    cursor: pointer;
}

#storeList .edit-store-btn:hover {
    background-color: #0056b3;
}          

.d-flex button {
    margin-left: 5px; /* Adjust spacing between buttons */
}

#filter-form {
        width: 100%; /* Form takes 90% of the container width */
        display: flex; /* Use flexbox for layout */
        align-items: center; /* Vertically align items */
    }

    #filter-form .form-group {
        width: 35%; /* Date pickers take 30% of the form width */
    }

    #filter-form .form-group-button {
        width: 20%; /* Button takes 20% of the form width */
    }


    </style>
</head>
<body>
    <!-- Navbar -->
    <nav id="top-navbar" class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <button id="burger-menu" class="navbar-toggler" type="button">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="#">
            @if(session('logo'))
                <img src="{{ asset('storage/' . session('logo')) }}" alt="Logo" style="max-width: 50px; max-height: 50px;">
            @endif
            {{ session('site_title', 'IMS') }}
        </a>
        

        <!-- Icons Always Visible on Mobile -->
        <div class="d-flex align-items-center ms-auto d-lg-none">
            <!-- Profile Icon -->
            <a class="nav-link p-2" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                <i class="bi bi-person"></i>
            </a>
            <!-- Settings Icon -->
            <a class="nav-link p-2" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal">
                <i class="bi bi-gear"></i>
            </a>
            <!-- Logout Icon -->
            <a class="nav-link p-2" href="#" onclick="event.preventDefault(); showLogoutModal();">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>

        <div class="search-bar d-flex align-items-center mx-auto" id="top-search">
            <input 
                type="text" 
                class="form-control" 
                placeholder="Search..." 
                aria-label="Search"
                id="search-input"
            />
        </div>

            <!-- Navbar Collapse for Desktop -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto text-center">
                    <!-- Profile -->
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center justify-content-center" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="bi bi-person me-2"></i>
                            <span class="d-none d-lg-inline">Profile</span>
                        </a>
                    </li>

                    <!-- Settings -->
                    <li class="nav-item">
                    <a class="nav-link d-flex align-items-center justify-content-center" 
                            href="#" 
                            data-bs-toggle="modal" 
                            data-bs-target="#settingsModal">
                                <i class="bi bi-gear me-2"></i>
                                <span class="d-none d-lg-inline">Settings</span>
                            </a>
                    </li>

                    <!-- Logout -->
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center justify-content-center" href="#" 
                        onclick="event.preventDefault(); showLogoutModal();">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            <span class="d-none d-lg-inline">Logout</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                        <form id="logout-expired-form" action="{{ route('logout.expired') }}" method="GET" style="display: none;">
                        </form>

                    </li>
                </ul>
            </div>

        </div>
    </nav>

    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <button id="close-btn" class="close-btn">&times;</button>

           <!-- User Info Section -->
           <div class="user-info">
    <!-- Display user's profile picture -->
    <img 
        src="{{ session('profile_picture', 'default-profile.jpg') }}" 
        alt="User Profile" 
        class="rounded-circle mb-2" 
        style="width: 80px; height: 80px; object-fit: cover;">
    
    <!-- Display user's name -->
    <h5>{{ session('user_name', 'User Name') }}</h5>
</div>



        <h5 class="text-center">Navigation</h5>
        <?php
// In your blade template
$mainModule = strtolower(session('main_module', '')); 
$subModules = array_map('strtolower', session('sub_modules', [])); 

// Fallback for main module
$defaultModule = $mainModule ?: ($subModules ? reset($subModules) : 'dashboard');

function checkPermission($module, $mainModule, $subModules) {
    // Convert to lowercase for comparison
    $module = strtolower($module);
    $mainModule = strtolower($mainModule);
    $subModules = array_map('strtolower', (array)$subModules);
    
    if ($module === 'dashboard') {
        return true;
    }
    return $module === $mainModule || in_array($module, $subModules);
}

$modules = [
    'order' => 'Order',
    'unreceived' => 'Unreceived',
    'receiving' => 'Receiving',
    'labeling' => 'Labeling',
    'validation' => 'Validation',
    'testing' => 'Testing',
    'cleaning' => 'Cleaning',
    'packing' => 'Packing',
    'stockroom' => 'Stockroom'
];
?>

<script>
    window.defaultComponent = "<?= session('main_module', 'dashboard') ?>".toLowerCase();
    window.allowedModules = <?= json_encode(array_map('strtolower', session('sub_modules', []))) ?>;
    window.mainModule = "<?= session('main_module', 'dashboard') ?>".toLowerCase();
</script>

<!-- Navigation structure with main module highlighted -->
<nav class="nav flex-column">
    <?php if ($mainModule): ?>
        <!-- If we have a main module, show it first -->
        <a class="nav-link active" href="#" 
           onclick="document.getElementById('<?= $mainModule ?>Link').click()">
            <?= $modules[$mainModule] ?? ucfirst($mainModule) ?>
        </a>
    <?php endif; ?>
    
    <?php foreach ($modules as $module => $label): ?>
        <?php if (checkPermission($module, $mainModule, $subModules) && $module !== $mainModule): ?>
            <a class="nav-link" href="#" 
               onclick="document.getElementById('<?= $module ?>Link').click()">
                <?= $label ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>


       
    </div>

    <div id="main-content" class="content">
    <div id="app">
    <!-- Hidden component triggers -->
    <?php foreach ($modules as $module => $label): ?>
        <a id="<?= $module ?>Link" 
           style="display:none" 
           href="#" 
           @click.prevent="loadContent('<?= $module ?>')">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
    
    <!-- Vue component with main module as default -->
    <component :is="currentComponent"></component>
</div>

    <div id="dynamic-content">
      @vite(['resources/js/app.js'])
    </div>
</div>

</div>


 <script>


document.addEventListener('DOMContentLoaded', function () {
    const settingsModal = document.getElementById('settingsModal');

    settingsModal.addEventListener('shown.bs.modal', function () {
        const defaultTab = document.querySelector('#design-tab');
        const defaultTabPane = document.querySelector('#design');

        // Ensure Bootstrap properly activates the tab
        if (defaultTab && defaultTabPane) {
            new bootstrap.Tab(defaultTab).show();
        }
    });

    settingsModal.addEventListener('hidden.bs.modal', function () {
        // Reset all tabs
        document.querySelectorAll('#settingsTab .nav-link').forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        });

        document.querySelectorAll('#settingsTabContent .tab-pane').forEach(tabPane => {
            tabPane.classList.remove('show', 'active');
        });

        // Reapply the default tab using Bootstrap's method
        const defaultTab = document.querySelector('#design-tab');
        if (defaultTab) {
            new bootstrap.Tab(defaultTab).show();
        }
    });
});

</script>
 <!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingsModalLabel">Admin Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="settingsTab" role="tablist">
                    <!-- Combined Tab for Title & Design -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="design-tab" data-bs-toggle="tab" data-bs-target="#design" type="button" role="tab" aria-controls="design" aria-selected="true">
                            <i class="bi bi-palette"></i>
                            <span class="d-none d-sm-inline"> Title & Design</span>
                        </button>
                    </li>
                    <!-- Add User Tab -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="user-tab" data-bs-toggle="tab" data-bs-target="#user" type="button" role="tab" aria-controls="user" aria-selected="false">
                            <i class="bi bi-person-plus"></i>
                            <span class="d-none d-sm-inline"> Add User</span>
                        </button>
                    </li>
                    <!-- Add Store List Tab -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="store-tab" data-bs-toggle="tab" data-bs-target="#store" type="button" role="tab" aria-controls="store" aria-selected="false">
                            <i class="bi bi-shop"></i>
                            <span class="d-none d-sm-inline"> Store List</span>
                        </button>
                    </li>
                    <!-- Privileges Tab -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="privilege-tab" data-bs-toggle="tab" data-bs-target="#privilege" type="button" role="tab" aria-controls="privilege" aria-selected="false">
                            <i class="bi bi-shield-lock"></i>
                            <span class="d-none d-sm-inline"> Privileges</span>
                        </button>
                    </li>
                    
                </ul>
         <!-- Combined Tab for Title & Design -->
                <div class="tab-content mt-3" id="settingsTabContent">
                    <!-- Title & Design Tab -->
                    <div class="tab-pane fade show active" id="design" role="tabpanel" aria-labelledby="design-tab">
                        <h5>Title & Design Settings</h5>
                       <!-- Title & Design Settings Form -->
                            <form action="{{ route('update.system.design') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('POST')
                                <!-- Site Title -->
                                <div class="mb-3">
                                    <label for="siteTitle" class="form-label">Site Title</label>
                                    <input type="text" class="form-control" id="siteTitle" name="site_title" placeholder="Enter site title" value="{{ $systemDesign->site_title ?? '' }}" required>
                                </div>
                                <!-- Theme Color -->
                                <div class="mb-3">
                                    <label for="themeColor" class="form-label">Theme Color</label>
                                    <input type="color" class="form-control" id="themeColor" name="theme_color" value="{{ $systemDesign->theme_color ?? '#007bff' }}" required>
                                </div>
                                <!-- Logo Upload -->
                                <div class="mb-3">
                                    <label for="logoUpload" class="form-label">Upload Logo</label>
                                    <input type="file" class="form-control" id="logoUpload" name="logo">
                                    @if (!empty($systemDesign->logo))
                                        <p>Current Logo: <img src="{{ asset('storage/' . $systemDesign->logo) }}" alt="Logo" width="100"></p>
                                    @endif
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                    </div>

                    <!-- Add User Tab -->
                    <div class="tab-pane fade" id="user" role="tabpanel" aria-labelledby="user-tab">
                    <h5>Add User</h5>
                    <form action="{{ route('add-user') }}" method="POST" id="addUserForm">
                        @csrf
                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm password" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password_confirmation">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- User Role -->
                        <div class="mb-3">
                            <label for="userRole" class="form-label">User Role</label>
                            <select class="form-select" id="userRole" name="role">
                                <option value="SuperAdmin">Super-Admin</option>
                                <option value="SubAdmin">Sub-Admin</option>
                                <option value="User">User</option>
                            </select>
                        </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">Add User</button>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#userListModal">
                                    <i class="bi bi-people me-2"></i>Show User List
                                </button>
                            </div>
                    </form>
                </div>
         
                <!-- Store List Tab Content -->
                <div class="tab-pane fade" id="store" role="tabpanel" aria-labelledby="store-tab">
                    <h5>Store List</h5>
                    <!-- Store List Display -->
                    <div id="storeListContainer">
                    <ul id="storeList" class="list-group">
                    <!-- New stores will be appended here dynamically -->
                </ul>

                    </div>
                    <!-- Add Store Button -->
                    <button class="btn btn-primary" id="addStoreButton">Add Store</button>
                </div>
            <!-- Store List Tab Content END-->  
             
          
            <div class="tab-pane fade" id="privilege" role="tabpanel" aria-labelledby="privilege-tab">
    <h5>User Privileges</h5>
    <form id="privilegeForm">
    @csrf
    <!-- Select User -->
    @php
        // Fetch all users directly in the Blade view
        $Allusers = \App\Models\User::all();
        // Determine which user is selected (default to admin if no user is selected)
        $selectedUser = request()->has('user_id') ? \App\Models\User::find(request('user_id')) : \App\Models\User::where('username', 'admin')->first();
    @endphp

    <label for="selectUser" class="form-label">Select User</label>
    <select class="form-select" id="selectUser" name="user_id" required>
        <!-- Default option (Select User) -->

        @foreach ($Allusers as $userOption)
            <option value="{{ $userOption->id }}"
                {{ isset($selectedUser) && $selectedUser->id == $userOption->id ? 'selected' : '' }}>
                {{ $userOption->username }}
            </option>
        @endforeach
    </select>

    <!-- Main Module -->
    <div id="mainModuleContainer"></div>

    <!-- Sub-Modules Privileges -->
    <div id="subModuleContainer"></div>

    <!-- Stores -->
    <div id="storeContainer"></div>

    <button type="submit" class="btn btn-primary">Save Privileges</button>
</form>
</div>


             </div>
          </div>
          <!--   <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div> -->
        </div>
    </div>
</div>
<script>
// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function() {
    const privilegeForm = document.getElementById('privilegeForm');
    if (privilegeForm) {
        initializeUserSelect();
        initializePrivilegeForm();
    } else {
        initializePrivilegeChecker();
    }
});

// Admin Functions
function initializeUserSelect() {
    const selectUser = document.getElementById('selectUser');
    
    selectUser.addEventListener('change', function() {
        const selectedValue = this.value;
        
        Array.from(this.options).forEach(option => {
            option.style.display = option.value === selectedValue ? 'none' : 'block';
        });
        
        if (selectedValue !== "") {
            const defaultOption = selectUser.querySelector('option[value=""]');
            if (defaultOption) {
                defaultOption.style.display = 'none';
            }
        }

        if (selectedValue) {
            fetchUserPrivileges(selectedValue);
        }
    });
}

function initializePrivilegeForm() {
    const form = document.getElementById('privilegeForm');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            // Refresh CSRF token before submitting
            await refreshCsrfToken();
            
            const formData = collectFormData();
            const response = await saveUserPrivileges(formData);
            
            if (response.success) {
                showNotification('Success', 'User privileges saved successfully!', 'success');
                
                await fetchUserPrivileges(formData.user_id);
                
                updateUserNavigation({
                    main_module: formData.main_module,
                    sub_modules: formData.sub_modules,
                    modules: {
                        'order': 'Order',
                        'unreceived': 'Unreceived',
                        'receiving': 'Receiving',
                        'labeling': 'Labeling',
                        'validation': 'Validation',
                        'testing': 'Testing',
                        'cleaning': 'Cleaning',
                        'packing': 'Packing',
                        'stockroom': 'Stockroom'
                    }
                });

                if (window.appInstance) {
                    forceComponentUpdate(formData.main_module);
                }

                // Get the modal element
                const modalEl = document.getElementById('settingsModal');
                modalEl.style.display = 'none';
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                modalEl.classList.remove('show');
                
                // Re-bind modal trigger
                const settingsButton = document.querySelector('[data-bs-toggle="modal"][data-bs-target="#settingsModal"]');
                if (settingsButton) {
                    settingsButton.setAttribute('data-bs-toggle', 'modal');
                    settingsButton.setAttribute('data-bs-target', '#settingsModal');
                }

                form.classList.remove('was-validated');
                initializeUserSelect();

            } else {
                showNotification('Error', response.message || 'Failed to save privileges', 'error');
            }
        } catch (error) {
            console.error('Error in form submission:', error);
            showNotification('Error', 'An unexpected error occurred', 'error');
        }
    });
}

// Add this new function to refresh CSRF token
async function refreshCsrfToken() {
    try {
        const response = await fetch('/csrf-token');
        const data = await response.json();
        document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.token);
        return true;
    } catch (error) {
        console.error('Error refreshing CSRF token:', error);
        return false;
    }
}

function collectFormData() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    return {
        user_id: parseInt(document.getElementById('selectUser').value, 10),
        main_module: document.querySelector('input[name="main_module"]:checked')?.value || '',
        sub_modules: [...document.querySelectorAll('input[name="sub_modules[]"]:checked')].map(input => input.value),
        privileges_stores: [...document.querySelectorAll('input[name="privileges_stores[]"]:checked')].map(input => input.value),
        _token: csrfToken
    };
}

async function saveUserPrivileges(formData) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    try {
        // First save the privileges
        const response = await fetch('/save-user-privileges', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Force session refresh
            const refreshResponse = await fetch('/refresh-user-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            
            const refreshResult = await refreshResponse.json();
            if (refreshResult.success) {
                return result;
            }
        }
        
        return result;
    } catch (error) {
        console.error('Error in save process:', error);
        throw error;
    }
}

async function fetchUserPrivileges(userId) {
    try {
        const response = await fetch(`/get-user-privileges/${userId}`);
        const data = await response.json();
        updateForm(data);
    } catch (error) {
        console.error('Error fetching user privileges:', error);
        showNotification('Error', 'Failed to fetch user privileges', 'error');
    }
}

function updateForm(data) {
    if (!data) {
        console.error("No data received for user privileges");
        return;
    }

    updateMainModule(data);
    updateSubModules(data);
    updateStores(data);
}

function updateMainModule(data) {
    const mainModules = ['Order', 'Unreceived', 'Receiving', 'Labeling', 'Testing', 'Cleaning', 'Packing', 'Stockroom'];
    const mainModuleHTML = `
        <h6>Main Module</h6>
        <div class="row mb-3">
            ${mainModules.map(module => `
                <div class="col-4 form-check mb-2 px-10">
                    <input class="form-check-input" type="radio" name="main_module" 
                           value="${module}" ${data.main_module === module ? 'checked' : ''} required>
                    <label class="form-check-label">${module}</label>
                </div>
            `).join('')}
        </div>
    `;
    document.getElementById('mainModuleContainer').innerHTML = mainModuleHTML;
}

function updateSubModules(data) {
    const subModules = ['Order', 'Unreceived', 'Receiving', 'Labeling', 'Testing', 'Cleaning', 'Packing', 'Stockroom'];
    const subModulesHTML = `
        <h6>Sub-Modules</h6>
        <div class="row mb-3">
            ${subModules.map(module => `
                <div class="col-4 form-check mb-2 px-10">
                    <input class="form-check-input" type="checkbox" name="sub_modules[]" 
                           value="${module}" ${data.sub_modules && data.sub_modules[module] ? 'checked' : ''}>
                    <label class="form-check-label">${module}</label>
                </div>
            `).join('')}
        </div>
    `;
    document.getElementById('subModuleContainer').innerHTML = subModulesHTML;
}

function updateStores(data) {
    const storeHTML = `
        <h6>Stores</h6>
        <div class="row mb-3">
            ${data.privileges_stores && data.privileges_stores.length > 0 
                ? data.privileges_stores.map(store => `
                    <div class="col-4 form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="privileges_stores[]" 
                               value="${store.store_column}" ${store.is_checked ? 'checked' : ''}>
                        <label class="form-check-label">${store.store_name}</label>
                    </div>
                `).join('')
                : '<p>No stores available</p>'
            }
        </div>
    `;
    document.getElementById('storeContainer').innerHTML = storeHTML;
}

// Navigation Update Functions
function initializePrivilegeChecker() {
    setInterval(checkForUpdates, 5000);
}

async function checkForUpdates() {
    try {
        const response = await fetch('/check-user-privileges');
        const data = await response.json();
        
        if (data.success) {
            console.log('Checking for updates:', data);
            
            window.defaultComponent = data.main_module;
            window.allowedModules = data.sub_modules;
            window.mainModule = data.main_module;

            updateUserNavigation(data);
        }
    } catch (error) {
        console.error('Error checking privileges:', error);
    }
}

function updateUserNavigation(data) {
    const nav = document.querySelector('nav.nav.flex-column');
    if (!nav) return;

    console.log('Updating navigation with:', data);

    let navHTML = '';

    // Add main module if it exists
    if (data.main_module) {
        navHTML += `
            <a class="nav-link active" href="#" 
               data-module="${data.main_module}"
               onclick="document.getElementById('${data.main_module}Link').click()">
                ${data.modules[data.main_module] || capitalizeFirst(data.main_module)}
            </a>`;
    }

    // Add sub modules
    if (Array.isArray(data.sub_modules)) {
        data.sub_modules.forEach(module => {
            if (module !== data.main_module) {
                navHTML += `
                    <a class="nav-link" href="#" 
                       data-module="${module}"
                       onclick="document.getElementById('${module}Link').click()">
                        ${data.modules[module] || capitalizeFirst(module)}
                    </a>`;
            }
        });
    }

    nav.innerHTML = navHTML;

    // Update Vue component if needed
    if (data.main_module && window.appInstance) {
        forceComponentUpdate(data.main_module);
    }
}

function forceComponentUpdate(moduleName) {
    if (!window.appInstance) return;
    
    console.log('Forcing update to component:', moduleName);
    window.appInstance.currentComponent = null;
    
    setTimeout(() => {
        window.appInstance.currentComponent = moduleName;
        console.log('Component updated to:', moduleName);
    }, 0);
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function showNotification(title, message, type) {
    alert(`${title}: ${message}`);
}

// Initialize form when page loads
window.onload = function() {
    const selectedUserId = document.getElementById('selectUser')?.value;
    if (selectedUserId) {
        fetchUserPrivileges(selectedUserId);
    }
};
</script>

<!-- Add Store Modal -->

<div class="modal fade" id="addStoreModal" tabindex="-1" aria-labelledby="addStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addStoreForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStoreModalLabel">Add New Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newStoreName" class="form-label">Store Name</label>
                        <input type="text" class="form-control" id="newStoreName" name="storename" placeholder="Enter store name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Store</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Store Modal -->
<div class="modal fade" id="editStoreModal" tabindex="-1" aria-labelledby="editStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStoreModalLabel">Edit Store</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStoreForm">
                    <input type='hidden' id="editStoreId">
                    <div class="mb-3">
                        <label for="editStoreName" class="form-label">Store Name</label>
                        <input type="text" class="form-control" id="editStoreName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editClientID" class="form-label">Client ID</label>
                        <input type="text" class="form-control" id="editClientID">
                    </div>
                    <div class="mb-3">
                        <label for="editClientSecret" class="form-label">Client Secret</label>
                        <input type="text" class="form-control" id="editClientSecret">
                    </div>
                    <div class="mb-3">
                        <label for="editRefreshToken" class="form-label">Refresh Token</label>
                        <input type="text" class="form-control" id="editRefreshToken">
                    </div>
                    <div class="mb-3">
                        <label for="editMerchantID" class="form-label">Merchant ID</label>
                        <input type="text" class="form-control" id="editMerchantID">
                    </div>

                    <div class="mb-3">
                        <label for="editMarketplace" class="form-label">Select Marketplace</label>
                        <select class="form-select" id="selectMarketplace" multiple>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="editMarketplace" class="form-label">Marketplace</label>
                        <input type="text" class="form-control" id="editMarketplace">
                    </div>

                    <div class="mb-3">
                        <label for="editMarketplaceID" class="form-label">Marketplace ID</label>
                        <input type="text" class="form-control" id="editMarketplaceID">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>




<!-- User List Modal -->
<div class="modal fade" id="userListModal" tabindex="-1" aria-labelledby="userListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userListModalLabel">User List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            <!-- Users will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    @csrf
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <!-- Username -->
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    
                    <!-- Password (Optional) -->
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password (leave blank to keep current)</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="edit_password" name="password">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#edit_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- User Role -->
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">User Role</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="SuperAdmin">Super-Admin</option>
                            <option value="SubAdmin">Sub-Admin</option>
                            <option value="User">User</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this user?
                <p class="text-danger" id="delete-user-name"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- PROFILE Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel"><b>PROFILE</b></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="settingsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab" aria-controls="attendance" aria-selected="true">
                            <i class="bi bi-calendar-check"></i>
                            <span class="d-none d-sm-inline"> Attendance</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="userprofile-tab" data-bs-toggle="tab" data-bs-target="#userprofile" type="button" role="tab" aria-controls="userprofile" aria-selected="false">
                            <i class="bi bi-person"></i>
                            <span class="d-none d-sm-inline"> Account</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="timerecord-tab" data-bs-toggle="tab" data-bs-target="#timerecord" type="button" role="tab" aria-controls="timerecord" aria-selected="false">
                            <i class="bi bi-clock"></i>
                            <span class="d-none d-sm-inline"> Record</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="myprivileges-tab" data-bs-toggle="tab" data-bs-target="#myprivileges" type="button" role="tab" aria-controls="myprivileges" aria-selected="false">
                            <i class="bi bi-shield-lock"></i>
                            <span class="d-none d-sm-inline"> My Privileges</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="settingsTabContent">

                    <!-- Attendance Tab -->
                    <div class="tab-pane fade show active text-center" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                        <h5>Attendance / Clock-in & Clock-out</h5>

                        <!-- Time, Day, and Date Display -->
                        <div class="mb-3">
                            <div id="current-time" style="font-size: 3rem; font-weight: bold;"></div>
                            <div id="current-day" style="font-size: 1.2rem; margin-top: 10px;"></div>
                            <div style="display:none;" id="current-date" style="font-size: 1.2rem; margin-top: 5px; color: #6c757d;"></div>
                        </div>

                        <!-- Clock In/Out Buttons -->
                        <div class="d-flex justify-content-center gap-3 mt-3">
                            <!-- Clock In Button -->
                            <form action="{{ route('attendance.clockin') }}" method="POST" id="clockin-form">
                                @csrf
                                <button type="button" 
                                        class="btn {{ !$lastRecord || ($lastRecord && $lastRecord->TimeIn && $lastRecord->TimeOut) ? 'btn-primary' : 'btn-secondary' }} px-4 py-3 fs-5" 
                                        style="min-width: 15%;"
                                        onclick="confirmClockIn()" 
                                        {{ !$lastRecord || ($lastRecord && $lastRecord->TimeIn && $lastRecord->TimeOut) ? '' : 'disabled' }}>
                                    Clock In
                                </button>
                            </form>

                            <!-- Clock Out Button -->
                            <form action="{{ route('attendance.clockout') }}" method="POST" id="clockout-form">
                                @csrf
                                <button type="button" 
                                        class="btn {{ $lastRecord && $lastRecord->TimeIn && !$lastRecord->TimeOut ? 'btn-primary' : 'btn-secondary' }} px-4 py-3 fs-5" 
                                        style="min-width: 15%;"
                                        onclick="confirmClockOut()" 
                                        {{ $lastRecord && $lastRecord->TimeIn && !$lastRecord->TimeOut ? '' : 'disabled' }}>
                                    Clock Out
                                </button>
                            </form>
                        </div>

                        <!-- Computations for Today's Hours and This Week's Hours -->
                        <div class="mt-4 p-3 bg-light border rounded">
                            <p><strong>Today's Hours:</strong> <span id="today-hours">{{ $todayHoursFormatted ?? '0:00' }}</span></p>
                            <p><strong>This Week's Hours:</strong> <span id="week-hours">{{ $weekHoursFormatted ?? '0:00' }}</span></p>
                        </div>              

                        <!-- Attendance Table -->
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Computed Hours</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($employeeClocksThisweek as $clockwk)
                                <tr data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $clockwk->Notes }}">

                                    <!-- Time In -->
                                    <td>
                                        {{ \Carbon\Carbon::parse($clockwk->TimeIn)->format('h:i A') }}
                                        <div class="text-muted">
                                            {{ \Carbon\Carbon::parse($clockwk->TimeIn)->format('M d, Y') }}
                                        </div>
                                    </td>

                                    <!-- Time Out -->
                                    <td>
                                        @if ($clockwk->TimeOut)
                                            {{ \Carbon\Carbon::parse($clockwk->TimeOut)->format('h:i A') }}
                                            <div class="text-muted">
                                                {{ \Carbon\Carbon::parse($clockwk->TimeOut)->format('M d, Y') }}
                                            </div>
                                        @else
                                            <span class="text-danger">Not yet timed out</span>
                                        @endif
                                    </td>

                                    <!-- Computed Hours -->
                                    <td id="computed-hours-{{ $clockwk->ID }}">
                                        <span class="text-muted">Not yet calculated</span></td>

                                    <!-- Update Button -->
                                     <td style="display:none;">
                                        <button
                                            class="btn btn-primary update-computed-hours d-none"
                                            data-id="{{ $clockwk->ID }}"
                                            data-timein="{{ $clockwk->TimeIn }}"
                                            data-timeout="{{ $clockwk->TimeOut }}">
                                            Update
                                        </button>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editNotesModal" 
                                                onclick="populateNotesModal('{{ $clockwk->ID }}', '{{ $clockwk->Notes }}')">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab -->
                    <div class="tab-pane fade" id="userprofile" role="tabpanel" aria-labelledby="userprofile-tab">
                        <h5>Change Password</h5>
                        <form action="{{ route('update-password') }}" method="POST">
                            @csrf
                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="myusername" name="myusername" placeholder="Enter username" value="{{ session('user_name', 'User Name') }}" required>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="newpassword" name="password" placeholder="Enter password" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="newpassword_confirmation" name="password_confirmation" placeholder="Confirm password" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password_confirmation">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">UPDATE</button>
                        </form>
                    </div>
					
                    
                    <!--  Tab -->
                    <div class="tab-pane fade show text-center" id="timerecord" role="tabpanel" aria-labelledby="timerecord-tab">

                        <div class="container">
                            <!-- Date Range Filter --> 
                                <form id="filter-form" class="mb-3">
                                    <!-- Start Date -->
                                    <div class="form-group">
                                        <label for="start-date" class="form-label visually-hidden">Start Date:</label>
                                        <input type="date" class="form-control" id="start-date" name="start_date" placeholder="Start Date">
                                    </div>

                                    <!-- End Date -->
                                    <div class="form-group">
                                        <label for="end-date" class="form-label visually-hidden">End Date:</label>
                                        <input type="date" class="form-control" id="end-date" name="end_date" placeholder="End Date">
                                    </div>

                                    <!-- Filter Button -->
                                    <div class="form-group-button">
                                        <button type="button" id="filter-button" class="btn btn-primary w-100">Filter</button>
                                    </div>
                                </form>

                            <!-- Computations -->
                            <strong><p>Total Hours: <span id="total-hours">0:00</span></p></strong>

                            <!-- Attendance Table -->
                            <div class="table-responsive mt-4">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Computed Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody id="attendance-table-body">
                                        <!-- Default Rows Will Be Loaded Dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                    <!-- Tab -->
                    <div class="tab-pane fade show" id="myprivileges" role="tabpanel" aria-labelledby="myprivileges-tab">
                        <h5 style="font-weight: bold; color: #333;">Account Privileges</h5>
                        <div class="row">
						
                            <!-- First Column -->
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="order" name="order" value="1" disabled>
                                    <label class="" for="order" style="font-size: 16px; font-weight: 500; color: #000;">
                                        Order
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="unreceived" name="unreceived" value="1" disabled>
                                    <label class="" for="unreceived" style="font-size: 16px; font-weight: 500; color: #000;">
                                        Unreceived
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="receiving" name="receiving" value="1" disabled>
                                    <label class="" for="receiving" style="font-size: 16px; font-weight: 500; color: #000;">
                                        Receiving
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="labeling" name="labeling" value="1" disabled>
                                    <label class="" for="labeling" style="font-size: 16px; font-weight: 500; color: #000;">
                                        Labeling
                                    </label>
                                </div>
                            </div>

                            <!-- Second Column -->
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="testing" name="testing" value="1" disabled>
                                    <label class="" for="testing" style="font-size: 16px; font-weight: 500; color: #000;">
                                        Testing
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cleaning" name="cleaning" value="1" disabled>
                                    <label class="" for="cleaning" style="font-size: 16px; font-weight: 500; color: #000;">
                                        Cleaning
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="packing" name="packing" value="1" disabled>
                                    <label class="" for="packing" style="font-size: 16px; font-weight: 500; color: #000;">
                                        Packing
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="stockroom" name="stockroom" value="1" disabled>
                                    <label class="" for="stockroom" style="font-size: 16px; font-weight: 500; color: #000;">
                                        Stockroom
                                    </label>
                                </div>
                            </div>
							
                        </div>
                    </div>



                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- NOTES Modal -->
<div class="modal fade" id="editNotesModal" tabindex="-1" aria-labelledby="editNotesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editNotesModalLabel">Edit Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editNotesForm">
                    @csrf
                    <input type="hidden" id="recordId" name="recordId">
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="updateNotes()">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editNotesModal = document.getElementById('editNotesModal');
    const profileModal = document.getElementById('profileModal');

    // Listen for the hidden.bs.modal event on the notes modal
    editNotesModal.addEventListener('hidden.bs.modal', function () {
        // Remove any remaining backdrop
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        // Show the profile modal again
        const profileModalInstance = new bootstrap.Modal(profileModal);
        profileModalInstance.show();
        // Ensure the attendance tab is active
        const attendanceTab = document.querySelector('#attendance-tab');
        if (attendanceTab) {
            attendanceTab.click();
        }
    });

    // When notes modal is about to show
    editNotesModal.addEventListener('show.bs.modal', function () {
        // Hide the profile modal properly
        const profileModalInstance = bootstrap.Modal.getInstance(profileModal);
        if (profileModalInstance) {
            profileModalInstance.hide();
        }
    });
});

function populateNotesModal(recordId, notes) {
    // Get modal instance
    const editNotesModal = new bootstrap.Modal(document.getElementById('editNotesModal'));
    
    // Set the values
    document.getElementById('recordId').value = recordId;
    document.getElementById('notes').value = notes;
}

function updateNotes() {
    const recordId = document.getElementById('recordId').value;
    const notes = document.getElementById('notes').value;
    const editNotesModal = bootstrap.Modal.getInstance(document.getElementById('editNotesModal'));

    // Send an AJAX request to update the Notes
    fetch(`/update-notes/${recordId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({ notes: notes }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide the notes modal first
            editNotesModal.hide();
            // Remove backdrop if present
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            // Show success message
            alert(data.message);
            // Reload the page
            location.reload();
        } else {
            alert('Failed to update notes.');
        }
    })
    .catch(error => {
        console.error('Error updating notes:', error);
        alert('An error occurred. Please try again.');
    });
}
</script>

<script>
 axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Show the add store modal and hide the settings modal
document.getElementById('addStoreButton').addEventListener('click', function() {
    // Show the add store modal
    $('#addStoreModal').modal('show');
    $('#settingsModal').modal('hide');
});

// Add Store Submission
document.getElementById('addStoreForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent default form submission

    const storeName = document.getElementById('newStoreName').value.trim();

    // Check if store name already exists in the list
    const existingStores = Array.from(document.getElementById('storeList').getElementsByTagName('li'));
    const storeExists = existingStores.some(store => store.textContent.includes(storeName));

    if (storeExists) {
        alert('Store name already exists. Please choose a different name.');
        return; // Prevent adding the store if the name already exists
    }

    // Send the data to the Laravel backend
    axios.post('/add-store', { storename: storeName })
        .then(response => {
            if (response.data.success) {
                const storeList = document.getElementById('storeList');
                const newStoreItem = document.createElement('li');
                newStoreItem.classList.add('list-group-item');
                newStoreItem.innerHTML = `
                    ${response.data.store.storename} 
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-secondary btn-sm edit-store-btn" 
                                data-id="${response.data.store.store_id}" 
                                data-name="${response.data.store.storename}">
                            Edit
                        </button>
                        <button class="btn btn-danger btn-sm delete-store-btn" 
                                data-id="${response.data.store.store_id}">
                            Delete
                        </button>
                    </div>
                `;
                storeList.appendChild(newStoreItem);

                // Hide the add store modal
                $('#addStoreModal').modal('hide');

                // Ensure the modal is fully closed before opening settings modal
                $('#addStoreModal').on('hidden.bs.modal', function () {
                    $('#settingsModal').modal('show');

                    // Ensure the store tab is active
                    $('.nav-tabs .nav-link').removeClass('active');
                    $('.tab-content .tab-pane').removeClass('active show');

                    $('#store-tab').addClass('active');
                    $('#store-tab-pane').addClass('active show');
                });
            } else {
                alert('Failed to add store');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the store.');
        });
});




// Fetch and display the list of stores on page load
document.addEventListener('DOMContentLoaded', function () {
    fetchStoreList();
});

// Function to fetch and display store list from the server
function fetchStoreList() {
    axios.get('/get-stores')
        .then(response => {
            const storeList = document.getElementById('storeList');
            storeList.innerHTML = ''; // Clear the list before populating it

            response.data.stores.forEach(store => {
                const listItem = document.createElement('li');
                listItem.classList.add('list-group-item');
                listItem.innerHTML = `
                    ${store.storename} 
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-secondary btn-sm edit-store-btn" 
                                data-id="${store.store_id}" 
                                data-name="${store.storename}">
                            Edit
                        </button>
                        <button class="btn btn-danger btn-sm delete-store-btn" 
                                data-id="${store.store_id}">
                            Delete
                        </button>
                    </div>
                `;
                storeList.appendChild(listItem);
            });
        })
        .catch(error => {
            console.error('Error fetching stores:', error);
        });
}

// Re-fetch store list when switching to the "Store List" tab
$('#store-tab').on('click', function() {
    fetchStoreList(); // Re-fetch the store list when the tab is clicked
});

function refreshStoreList() {
    const userId = document.getElementById('selectUser').value;
    if (!userId) {
        console.warn('No user selected');
        return;
    }

    showLoadingIndicator();
    
    fetch(`/fetchNewlyAddedStoreCol?user_id=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.stores) {
                updateStoreList(data.stores);
            }
        })
        .catch(error => {
            console.error('Error fetching store list:', error);
            showErrorMessage('Failed to load stores. Please try again.');
        })
        .finally(() => {
            hideLoadingIndicator();
        });
}

function updateStoreList(stores) {
    const storeContainer = document.getElementById('storeContainer');
    
    // Save current checkbox states
    const currentStates = new Map();
    document.querySelectorAll('input[name="privileges_stores[]"]').forEach(input => {
        currentStates.set(input.value, input.checked);
    });

    let storeListHTML = '<h6>Stores</h6><div class="row mb-3">';
    
    stores.forEach(store => {
        // Check if we have a saved state, otherwise use the server state
        const isChecked = currentStates.has(store.store_column) 
            ? currentStates.get(store.store_column)
            : store.is_checked;
            
        storeListHTML += `
            <div class="col-4 form-check mb-2">
                <input class="form-check-input" 
                       type="checkbox"
                       name="privileges_stores[]"
                       value="${store.store_column}"
                       ${isChecked ? 'checked' : ''}>
                <label class="form-check-label">${store.store_name}</label>
            </div>`;
    });
    
    storeListHTML += '</div>';
    storeContainer.innerHTML = storeListHTML;
}

function showLoadingIndicator() {
    const container = document.getElementById('storeContainer');
    container.innerHTML += '<div class="loading-spinner">Loading stores...</div>';
}

function hideLoadingIndicator() {
    const spinner = document.querySelector('.loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

function showErrorMessage(message) {
    document.getElementById('storeContainer').innerHTML = 
        `<div class="alert alert-danger">${message}</div>`;
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize privilege tab listener
    const privilegeTab = document.getElementById('privilege-tab');
    if (privilegeTab) {
        privilegeTab.addEventListener('click', function() {
            const userId = document.getElementById('selectUser').value;
            if (userId) {
                refreshStoreList();
            }
        });
    }

    // Initialize select user change listener
    const selectUser = document.getElementById('selectUser');
    if (selectUser) {
        selectUser.addEventListener('change', function() {
            if (this.value) {
                refreshStoreList();
            }
        });
    }
});
// Delete Store functionality
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-store-btn')) {
        const storeId = e.target.dataset.id;

        // Confirm before deleting
        if (confirm('Are you sure you want to delete this store?')) {
            // Send the delete request to the backend
            axios.delete(`/delete-store/${storeId}`)
                .then(response => {
                    if (response.data.success) {
                        const storeItem = e.target.closest('li');
                        storeItem.remove();
                    }
                })
                .catch(error => {
                    console.error('Error deleting store:', error);
                    alert('An error occurred while deleting the store. Please try again later.');
                });
        }
    }
});

$(document).on('click', '.edit-store-btn', function() {
    const storeId = $(this).data('id');
    $('#settingsModal').modal('hide');
    // Fetch the store details using the store ID
    axios.get(`/get-store/${storeId}`)
        .then(response => {
            const store = response.data.store;

            // Populate the modal with the current store details
            $('#editStoreId').val(store.store_id);
            $('#editStoreName').val(store.storename);
            $('#editClientID').val(store.client_id);
            $('#editClientSecret').val(store.client_secret);
            $('#editRefreshToken').val(store.refresh_token);
            $('#editMerchantID').val(store.MerchantID);
            $('#editMarketplace').val(store.Marketplace);
            $('#editMarketplaceID').val(store.MarketplaceID);

            // Show the modal
            $('#editStoreModal').modal('show');
        })
        .catch(error => {
            console.error('Error fetching store details:', error);
            alert('An error occurred while fetching store details.');
        });
});

document.getElementById('editStoreForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent default form submission

    const storeId = document.getElementById('editStoreId').value.trim();
    if (!storeId) {
        alert('Store ID is missing. Please try again.');
        return;
    }

    // Gather the updated data from the form
    const updatedStoreData = {
        store_id: storeId,  // Should match the store_id column in the database
        storename: document.getElementById('editStoreName').value.trim() || null,
        client_id: document.getElementById('editClientID').value.trim() || null,
        client_secret: document.getElementById('editClientSecret').value.trim() || null,
        refresh_token: document.getElementById('editRefreshToken').value.trim() || null,
        MerchantID: document.getElementById('editMerchantID').value.trim() || null,
        Marketplace: document.getElementById('editMarketplace').value.trim() || null,
        MarketplaceID: document.getElementById('editMarketplaceID').value.trim() || null
    };

    console.log(updatedStoreData);

    // Send request to update store
    axios.post('/update-store/' + storeId, updatedStoreData, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        console.log(response);
        if (response.data.success) {
            alert('Store updated successfully');
            fetchStoreList();
            $('#editStoreModal').modal('hide');
            $('#settingsModal').modal('show');
            $('#store-tab').tab('show');
        } else {
            // Display the error message returned by the server
            alert(response.data.message || 'Failed to update store');
        }
    })
    .catch(error => {
        console.error('Error updating store:', error);
        alert('An error occurred while updating the store.');
    });
});


// Alternatively, if you're using the close button explicitly, you can handle it like this:
    document.querySelector('#editStoreModal .btn-close').addEventListener('click', function() {
    // Show the settings modal and select the store tab after closing the edit modal
    $('#settingsModal').modal('show');
    $('#store-tab').tab('show'); // This activates the store tab
});


function fetchMarketplaces() {
    console.log("Modal is shown, fetching marketplaces..."); // Check if the modal is opening
    axios.get('/fetch-marketplaces')
        .then(response => {
            const marketplaceSelect = document.getElementById('selectMarketplace');
            marketplaceSelect.innerHTML = ''; // Clear existing options

            response.data.forEach(marketplace => {
                const option = document.createElement('option');
                option.value = marketplace.value; // Set the 'value' field
                option.textContent = marketplace.name; // Display the 'name' field
                marketplaceSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching marketplaces:', error);
        });
}

function updateMarketplaceFields() {
    const marketplaceSelect = document.getElementById('selectMarketplace');
    const selectedOptions = Array.from(marketplaceSelect.selectedOptions);

    // Retrieve existing values from the input fields
    const currentNames = document.getElementById('editMarketplace').value.split(',').map(name => name.trim());
    const currentIDs = document.getElementById('editMarketplaceID').value.split(',').map(id => id.trim());

    // Add new values, avoiding duplicates
    selectedOptions.forEach(option => {
        if (!currentNames.includes(option.textContent)) {
            currentNames.push(option.textContent);
            currentIDs.push(option.value);
        }
    });

    // Update the fields with the updated values
    document.getElementById('editMarketplace').value = currentNames.filter(Boolean).join(', ');
    document.getElementById('editMarketplaceID').value = currentIDs.filter(Boolean).join(', ');
}

// Attach event listeners
document.getElementById('editStoreModal').addEventListener('show.bs.modal', fetchMarketplaces);
document.getElementById('selectMarketplace').addEventListener('change', updateMarketplaceFields);

</script>   
<!-- Success Notification for adding user-->
@if (session('success'))
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="successToast" class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                {{ session('success') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif

<!-- Error Notification -->
@if (session('error'))
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="errorToast" class="toast align-items-center text-bg-danger border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                {{ session('error') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif

<!-- Validation Errors -->
@if ($errors->any())
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="validationToast" class="toast align-items-center text-bg-warning border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif


<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Automatically show all toasts on page load
        const toastElList = [].slice.call(document.querySelectorAll('.toast'));
        toastElList.forEach(function (toastEl) {
            new bootstrap.Toast(toastEl).show();
        });
    });
    
    document.addEventListener('DOMContentLoaded', () => {
        // Add click event listeners to all toggle-password buttons
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', () => {
                const targetInput = document.querySelector(button.getAttribute('data-target'));
                const icon = button.querySelector('i');
                
                if (targetInput.type === 'password') {
                    targetInput.type = 'text'; // Show password
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    targetInput.type = 'password'; // Hide password
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    });
</script>



<!-- Audio Elements -->
<audio id="clockin-sound" src="/sounds/clockin2.mp3"></audio>
<audio id="clockout-sound" src="/sounds/clockout2.mp3"></audio>
<audio id="clockin-question-sound" src="/sounds/clockin_question.mp3"></audio>
<audio id="clockout-question-sound" src="/sounds/clockout_question.mp3"></audio>
<audio id="error-sound" src="/sounds/error2.mp3"></audio>
<audio id="logout-sound" src="/sounds/logout.mp3"></audio>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Success</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="fs-4" id="successMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="errorModalLabel">Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="fs-4">{{ session('error') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Get the audio elements
        const clockinSound = document.getElementById('clockin-sound');
        const clockoutSound = document.getElementById('clockout-sound');
        const errorSound = document.getElementById('error-sound');
        // Get the modal and message elements
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        const successMessage = document.getElementById('successMessage');

        // Check conditions for playing sounds
        @if (session('success_clockin'))
            successMessage.textContent = "{{ session('success_clockin') }}";
            successModal.show();
            clockinSound.play();
        @endif

        @if (session('success_clockout'))
            successMessage.textContent = "{{ session('success_clockout') }}";
            successModal.show();
            clockoutSound.play();
        @endif

        // Show error modal and play error sound if an error message exists
        @if (session('error'))
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
            errorSound.play();
        @endif
    });

</script>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to logout?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="button" class="btn btn-danger" id="confirmLogout">Yes</button>
            </div>
        </div>
    </div>
</div>

<script>
        const logoutSound = document.getElementById('logout-sound');
    // Show the logout confirmation modal
    function showLogoutModal() {

        if (document.getElementsByName('_token').length === 0) {
        window.location.href = '/logout';
        return;
    }
        const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
        logoutModal.show();
        logoutSound.play();
    }

    // Handle the "Yes" button click in the modal
    document.getElementById('confirmLogout').addEventListener('click', function () {
        document.getElementById('logout-form').submit();
    });


    function keepSessionAlive() {
    fetch('/keep-alive', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).catch(error => console.log('Session refresh failed:', error));
}

        // Ping every 10 minutes
        setInterval(keepSessionAlive, 600000);
</script>

    <!-- Footer -->
    <footer>
        &copy; 2025 IMS (Inventory Management System). All rights reserved.
    </footer>

    <script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('main-content');
const burgerMenu = document.getElementById('burger-menu');
const closeBtn = document.getElementById('close-btn');
const navbarBrand = document.querySelector('.navbar-brand');
const dynamicContent = document.getElementById('dynamic-content');
const searchContainer = document.getElementById('top-search');
const searchInput = document.getElementById('search-input');
let showSearch = false; // Initially hide search for dashboard

// Function to toggle sidebar visibility
burgerMenu.addEventListener('click', () => {
    const isMobile = window.innerWidth <= 768;
    if (isMobile) {
        sidebar.classList.toggle('visible');
    } else {
        sidebar.classList.toggle('visible');
        mainContent.classList.toggle('sidebar-visible');
        navbarBrand.classList.toggle('shifted');
        burgerMenu.classList.toggle('hidden');
    }
});

// Hide sidebar when close button is clicked
closeBtn.addEventListener('click', () => {
    sidebar.classList.remove('visible');
    if (window.innerWidth > 768) {
        mainContent.classList.remove('sidebar-visible');
        navbarBrand.classList.remove('shifted');
        burgerMenu.classList.remove('hidden');
    }
});




function initSearch(module) {
    const searchInput = document.querySelector('#top-search input');
    const dataTable = document.querySelector('.custom-table tbody'); // For table view
    const mobileView = document.querySelector('.mobile-view'); // For mobile view

    if (searchInput && (dataTable || mobileView)) {
        searchInput.addEventListener("input", function () {
            const filter = searchInput.value.toLowerCase();

            if (dataTable) {
                // Handle search for table view
                const rows = dataTable.querySelectorAll("tr");
                rows.forEach(row => {
                    const cells = row.querySelectorAll("td");
                    let rowText = '';
                    cells.forEach(cell => {
                        rowText += cell.textContent.toLowerCase();
                    });
                    row.style.display = rowText.includes(filter) ? "" : "none";
                });
            }

            if (mobileView) {
                // Handle search for mobile view (card layout)
                const rows = mobileView.querySelectorAll(".custom-table-row");
                rows.forEach(row => {
                    let rowText = row.textContent.toLowerCase();
                    row.style.display = rowText.includes(filter) ? "" : "none";
                });
            }
        });
    }
}



document.addEventListener('DOMContentLoaded', () => {
    // Function to update time, day, and date in US Pacific Time
    function updateTime() {
        const currentTimeElement = document.getElementById('current-time');
        const currentDayElement = document.getElementById('current-day');
        const currentDateElement = document.getElementById('current-date');

        if (currentTimeElement && currentDayElement && currentDateElement) {
            // Get current date and time in US Pacific Time
            const now = new Date();

            // Format the time in 12-hour format with AM/PM
            const pacificTime = new Intl.DateTimeFormat('en-US', {
                timeZone: 'America/Los_Angeles',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true, // Enable 12-hour format
            }).formatToParts(now);

            // Extract time parts
            const hours = pacificTime.find(part => part.type === 'hour').value;
            const minutes = pacificTime.find(part => part.type === 'minute').value;
            const seconds = pacificTime.find(part => part.type === 'second').value;
            const period = pacificTime.find(part => part.type === 'dayPeriod').value; // AM or PM

            const formattedTime = `${hours}:${minutes}:${seconds} ${period}`;

            // Get day and date in Pacific Time
            const pacificDay = new Intl.DateTimeFormat('en-US', {
                timeZone: 'America/Los_Angeles',
                weekday: 'long',
            }).format(now);

            const pacificDate = new Intl.DateTimeFormat('en-US', {
                timeZone: 'America/Los_Angeles',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }).format(now);

            // Update the elements
            currentTimeElement.textContent = formattedTime; // Display time with AM/PM
            currentDayElement.textContent = pacificDay + " , " + pacificDate; // Display the day
            currentDateElement.textContent = pacificDate; // Display the date
        }
    }

    // Update the time, day, and date immediately and then every second
    updateTime();
    setInterval(updateTime, 1000);
});

  </script>
  
  

<script>
const clockin_question_Sound = document.getElementById('clockin-question-sound');
const clockout_question_Sound = document.getElementById('clockout-question-sound'); 

    function confirmClockIn() {
        clockin_question_Sound.play();
        if (confirm('Are you sure you want to Clock In?')) {
            document.getElementById('clockin-form').submit();
        }
    }

    function confirmClockOut() {
        clockout_question_Sound.play();
        if (confirm('Are you sure you want to Clock Out?')) {
            document.getElementById('clockout-form').submit();
        }
    }
</script>

<script>
$(document).ready(function () {
    function updateComputedHours(clockId, timeIn, timeOut) {
        $.ajax({
            url: "{{ route('update.computed.hours') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                timeIn: timeIn,
                timeOut: timeOut,
            },
            success: function (response) {
                const computedCell = $(`#computed-hours-${clockId}`);
                computedCell.html(`${response.hours} hrs ${response.minutes} mins`);
                if (response.message) {
                    computedCell.append(`<div class="text-muted">(${response.message})</div>`);
                }
            },
            error: function (error) {
                console.error("Error updating computed hours:", error);
            }
        });
    }

    // Function to loop through all rows and update computed hours
    function updateAllComputedHours() {
        $('.update-computed-hours').each(function () {
            const clockId = $(this).data('id'); // Get clock ID
            const timeIn = $(this).data('timein'); // Get TimeIn
            const timeOut = $(this).data('timeout'); // Get TimeOut (or null)

            updateComputedHours(clockId, timeIn, timeOut);
        });
    }

    // Call updateAllComputedHours every 30 seconds
    setInterval(updateAllComputedHours, 30000); // 30,000 milliseconds = 30 seconds

    // Optionally, call it once when the page loads
    updateAllComputedHours();

    function updateHours() {
            $.ajax({
                url: "{{ route('attendance.update.hours') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    // Update Today's Hours and This Week's Hours
                    $('#today-hours').text(response.todayHours);
                    $('#week-hours').text(response.weekHours);
                },
                error: function (error) {
                    console.error("Error updating hours:", error);
                }
            });
        }

        // Call updateHours every 30 seconds
        setInterval(updateHours, 30000); // 30,000 milliseconds = 30 seconds

        // Optionally, call it once when the page loads
        updateHours();
});

</script>

<script>
    $(document).ready(function () {

        // Function to fetch attendance data
        function fetchAttendanceData(startDate = null, endDate = null) {
            $.ajax({
                url: "{{ route('attendance.filter.ajax') }}", // AJAX route
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    start_date: startDate,
                    end_date: endDate
                },
                success: function (response) {
                    const tableBody = $('#attendance-table-body');
                    const totalHoursSpan = $('#total-hours');
                    tableBody.empty(); // Clear the table body
                    let totalMinutes = 0;

                    if (response.employeeClocks.length > 0) {
                        response.employeeClocks.forEach(function (clock) {
                            const timeIn = new Date(clock.time_in);
                            const timeOut = clock.time_out
                                ? new Date(clock.time_out)
                                : new Date(new Date().toLocaleString('en-US', { timeZone: 'America/Los_Angeles' }));
                            const diffInMinutes = Math.round((timeOut - timeIn) / 60000);
                            totalMinutes += diffInMinutes;
                            const hours = Math.floor(diffInMinutes / 60);
                            const minutes = diffInMinutes % 60;

                            // Append row
                            tableBody.append(`
                                <tr>
                                    <td>
                                        ${timeIn.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                        <div class="text-muted">
                                            ${timeIn.toLocaleDateString()}
                                        </div>
                                    </td>
                                    <td>
                                        ${
                                            clock.time_out
                                                ? `${timeOut.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                   <div class="text-muted">${timeOut.toLocaleDateString()}</div>`
                                                : '<span class="text-danger">Not yet timed out</span>'
                                        }
                                    </td>
                                    <td>${hours} hrs ${minutes} mins</td>
                                </tr>
                            `);
                        });

                        // Calculate total hours from totalMinutes
                        const totalHours = Math.floor(totalMinutes / 60);
                        const totalRemainingMinutes = totalMinutes % 60;
                        totalHoursSpan.text(`${totalHours} hrs ${totalRemainingMinutes} mins`);
                    } else {
                        tableBody.append(`
                            <tr>
                                <td colspan="3" class="text-center">No records found.</td>
                            </tr>
                        `);
                        totalHoursSpan.text('0:00');
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        }

        // Load default 10 rows on page load
        fetchAttendanceData();

        // Fetch data on filter button click
        $('#filter-button').on('click', function () {
            const startDate = $('#start-date').val();
            const endDate = $('#end-date').val();
            fetchAttendanceData(startDate, endDate);
        });
        
    });

    document.addEventListener('DOMContentLoaded', function () {
    function autoClockOut() {
        const lastRecordTimeIn = "{{ $verylastRecord ? $verylastRecord->TimeIn : null }}"; // Fetch TimeIn from server-side variable
        if (!lastRecordTimeIn) return; // Exit if no TimeIn is available

        // Convert TimeIn to a Date object
        const timeInDate = new Date(lastRecordTimeIn);
        const currentDate = new Date(
            new Date().toLocaleString('en-US', { timeZone: 'America/Los_Angeles' })
        );

        // Check if TimeIn is not today
        const isNotToday = timeInDate.toLocaleDateString() !== currentDate.toLocaleDateString();

        // Check if TimeIn is more than 8 hours ago
        const eightHoursAgo = new Date(currentDate.getTime() - 8 * 60 * 60 * 1000); // Subtract 8 hours from the current time
        const isMoreThan8HoursAgo = timeInDate < eightHoursAgo;

        // Auto clock out if either condition is true
        if (isNotToday || isMoreThan8HoursAgo) {
            console.log("Auto Clocking Out: TimeIn is not today or more than 8 hours ago.");

            // Send the request to auto clock-out
            fetch("{{ route('auto-clockout') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({}),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    //console.log(data.message);
                    // Reload the page after a short delay to show updated data
                    setTimeout(() => location.reload(), 1000);
                } else {
                    //console.error(data.message);
                }
            })
            .catch(error => {
                //console.error("Error during auto clock-out:", error);
            });
        }
    }

    // Call the function after 30 seconds
    setTimeout(autoClockOut, 30000);
    autoClockOut();
});

// Fetch privileges data when the page loads
document.addEventListener('DOMContentLoaded', function () {
    let lastPrivileges = null; // Store last fetched privileges

    // Function to fetch privileges and update checkboxes if there are changes
    function fetchPrivileges() {
        fetch('{{ route('myprivileges') }}')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const privileges = data.data;

                    // Compare with last fetched data to detect changes
                    if (JSON.stringify(lastPrivileges) !== JSON.stringify(privileges)) {
                        console.log("Privileges updated, applying changes...");
                        
                        // Update checkboxes dynamically
                        document.getElementById('order').checked = privileges.order === 1;
                        document.getElementById('unreceived').checked = privileges.unreceived === 1;
                        document.getElementById('receiving').checked = privileges.receiving === 1;
                        document.getElementById('labeling').checked = privileges.labeling === 1;
                        document.getElementById('testing').checked = privileges.testing === 1;
                        document.getElementById('cleaning').checked = privileges.cleaning === 1;
                        document.getElementById('packing').checked = privileges.packing === 1;
                        document.getElementById('stockroom').checked = privileges.stockroom === 1;

                        // Store the new data as the last fetched data
                        lastPrivileges = privileges;
                    }
                } else {
                    console.error('Error fetching privileges:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    // Fetch privileges initially when page loads
    fetchPrivileges();

    // Set interval to check for updates every 5 seconds
    //setInterval(fetchPrivileges, 5000);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userListModal = document.getElementById('userListModal');
    const settingsModal = document.getElementById('settingsModal');
    const addUserForm = document.getElementById('addUserForm');

    // Function to fetch and display users
    function fetchUsers() {
        fetch('{{ route("user") }}')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const userTableBody = document.getElementById('userTableBody');
                    let html = '';

                    data.data.forEach(user => {
                        const createdAt = new Date(user.created_at).toLocaleString();
                        const badgeClass = user.role === 'SuperAdmin' ? 'bg-danger' : 
                                         (user.role === 'SubAdmin' ? 'bg-warning' : 'bg-info');

                        html += `
                            <tr>
                                <td>${user.username}</td>
                                <td><span class="badge ${badgeClass}">${user.role}</span></td>
                                <td>${createdAt}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editUser(${user.id}, '${user.username}', '${user.role}')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="showDeleteConfirmation(${user.id}, '${user.username}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });

                    userTableBody.innerHTML = html || '<tr><td colspan="4" class="text-center">No users found</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('userTableBody').innerHTML = 
                    '<tr><td colspan="4" class="text-center text-danger">Error loading users</td></tr>';
            });
    }

    // User List Modal event handlers
    userListModal.addEventListener('hidden.bs.modal', function (event) {
        // Check if edit modal is being shown
        const editModalElement = document.getElementById('editUserModal');
        if (editModalElement.classList.contains('show')) {
            return; // Don't do anything if edit modal is being shown
        }
        
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    });

    userListModal.addEventListener('show.bs.modal', function () {
        const settingsModalInstance = bootstrap.Modal.getInstance(settingsModal);
        if (settingsModalInstance) {
            settingsModalInstance.hide();
        }
        fetchUsers();
    });

    // Add User Form handler
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('{{ route("add-user") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => Promise.reject(data));
                }
                return response.json();
            })
            .then(data => {
                if(data.success) {
                    const settingsModalInstance = bootstrap.Modal.getInstance(settingsModal);
                    if (settingsModalInstance) {
                        settingsModalInstance.hide();
                    }

                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    
                    this.reset();
                    alert('User added successfully!');
                    
                    const userListModalInstance = new bootstrap.Modal(userListModal);
                    userListModalInstance.show();
                    fetchUsers();
                } else {
                    throw new Error(data.message || 'Failed to add user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Error adding user. Please try again.');
            });
        });
    }

    // Edit User Functions
    window.editUser = function(userId, username, role) {
        // Get modal instances
        const userListModalInstance = bootstrap.Modal.getInstance(document.getElementById('userListModal'));
        const settingsModalInstance = bootstrap.Modal.getInstance(document.getElementById('settingsModal'));

        // Hide both modals
        if (userListModalInstance) {
            userListModalInstance.hide();
        }
        if (settingsModalInstance) {
            settingsModalInstance.hide();
        }

        setTimeout(() => {
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }, 100);

        // Populate edit form
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_password').value = '';

        setTimeout(() => {
            const editModalInstance = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModalInstance.show();
        }, 150);
    };

    // Edit form submission handler
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const userId = document.getElementById('edit_user_id').value;

        fetch(`/update-user/${userId}`, {
            method: 'POST',
            body: new FormData(this),
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User updated successfully!');
                const editModal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                editModal.hide();
                
                const userListModal = new bootstrap.Modal(document.getElementById('userListModal'));
                userListModal.show();
                fetchUsers();
            } else {
                alert(data.message || 'Error updating user');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating user');
        });
    });

    // Edit modal hidden event handler
    document.getElementById('editUserModal').addEventListener('hidden.bs.modal', function (event) {
        event.stopPropagation();
        
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        
        setTimeout(() => {
            const userListModalInstance = new bootstrap.Modal(document.getElementById('userListModal'));
            userListModalInstance.show();
        }, 100);
    });

    // Delete User Functions
    let deleteUserId = null;

    window.showDeleteConfirmation = function(userId, username) {
        deleteUserId = userId;
        document.getElementById('delete-user-name').textContent = username;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        deleteModal.show();
    };

    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (deleteUserId) {
            fetch(`/delete-user/${deleteUserId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User deleted successfully!');
                    const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteUserModal'));
                    deleteModal.hide();
                    fetchUsers();
                } else {
                    alert(data.message || 'Error deleting user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting user');
            });
        }
    });
});
</script>
</body>
</html>