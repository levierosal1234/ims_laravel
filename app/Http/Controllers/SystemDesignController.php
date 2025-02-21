<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\SystemDesign;
use App\Services\UserLogService;

class SystemDesignController extends Controller
{

    protected $userLogService;

    public function __construct(UserLogService $userLogService) {
        $this->userLogService = $userLogService;
    }

    public function update(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'site_title' => 'required|string|max:255',
            'theme_color' => 'required|string|max:7',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        // Retrieve the existing system design record (or create a new one)
        $systemDesign = SystemDesign::first();
    
        // Update the site title, theme color and logo if a new logo is uploaded
        $systemDesign->site_title = $request->site_title;
        $systemDesign->theme_color = $request->theme_color;
    
        if ($request->hasFile('logo')) {
            // Delete the old logo if it exists
            if ($systemDesign->logo) {
                Storage::delete($systemDesign->logo);
            }
    
            // Store the new logo and update the path
            $path = $request->file('logo')->store('logos', 'public');
            $systemDesign->logo = $path;
        }
    
        // Save the updated system design record to the database
        $systemDesign->save();
    
        // Update the session with the new values so they reflect immediately
        session([
            'site_title' => $systemDesign->site_title,
            'theme_color' => $systemDesign->theme_color,
            'logo' => $systemDesign->logo,
        ]);

// Log using service
        $this->userLogService->log('Update the System Design');
    
        // Return a success response
        return back()->with('success', 'System design updated successfully!');
    }
    
}
