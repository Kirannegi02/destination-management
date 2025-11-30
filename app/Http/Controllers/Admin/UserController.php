<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the users (agents).
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('agency_name', 'like', "%{$search}%")
                  ->orWhere('gst_number', 'like', "%{$search}%");
            });
        }

        // Filter by status (default to pending if not specified)
        $status = $request->get('status', 'pending');
        
        if ($status === 'approved') {
            // Approved tab shows both active and inactive users
            $query->whereIn('status', ['active', 'inactive']);
        } elseif ($status !== '') {
            // Pending tab shows only pending users
            $query->where('status', $status);
        }

        // Filter by profile completion
        if ($request->has('profile_completed')) {
            if ($request->profile_completed == '1') {
                $query->whereNotNull('profile_completed_at');
            } else {
                $query->whereNull('profile_completed_at');
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get counts for tabs
        $pendingCount = User::where('status', 'pending')->count();
        $approvedCount = User::whereIn('status', ['active', 'inactive'])->count();

        return view('admin.users.index', compact('users', 'status', 'pendingCount', 'approvedCount'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        
        // Determine which tab the user came from based on their status
        // If user is pending, they came from pending tab
        // If user is active/inactive, they came from approved tab
        $returnStatus = $user->status === 'pending' ? 'pending' : 'approved';
        
        // Override with query parameter if provided
        if ($request->has('status')) {
            $returnStatus = $request->get('status');
        }
        
        return view('admin.users.edit', compact('user', 'returnStatus'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'agency_name' => 'required|string|max:255',
            'gst_number' => 'required|string|max:15|unique:users,gst_number,' . $user->id,
            'address' => 'required|string',
            'country' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive,pending',
        ]);

        // Validate GST number format
        $gstNumber = strtoupper(trim($validated['gst_number']));
        if (!$this->validateGstNumber($gstNumber)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['gst_number' => 'Invalid GST number format. Must be 15 characters.']);
        }
        $validated['gst_number'] = $gstNumber;

        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $imageData = ImageService::update(
                    $request->file('image'),
                    $user->image,
                    'agents',
                    (string) $user->id,
                    2048
                );
                $validated['image'] = $imageData['path'];
            } catch (\Exception $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['image' => 'Image upload failed: ' . $e->getMessage()]);
            }
        } else {
            // Keep existing image if not uploading new one
            unset($validated['image']);
        }

        $user->update($validated);

        // Determine which tab to redirect to based on the updated status
        // If status is pending, redirect to pending tab
        // If status is active/inactive, redirect to approved tab
        $redirectStatus = $validated['status'] === 'pending' ? 'pending' : 'approved';

        return redirect()
            ->route('admin.users.index', ['status' => $redirectStatus])
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // Determine which tab to redirect to based on the user's status before deletion
        $redirectStatus = $user->status === 'pending' ? 'pending' : 'approved';
        
        // Delete user's image if exists
        if ($user->image) {
            ImageService::delete($user->image);
        }
        
        $user->delete();

        return redirect()
            ->route('admin.users.index', ['status' => $redirectStatus])
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Validate GST number format (Indian GST format)
     */
    private function validateGstNumber($gstNumber)
    {
        $gstNumber = strtoupper(trim($gstNumber));
        
        if (strlen($gstNumber) !== 15) {
            return false;
        }
        
        $pattern = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[0-9A-Z]{1}Z[0-9A-Z]{1}$/';
        
        if (!preg_match($pattern, $gstNumber)) {
            return false;
        }
        
        $stateCode = (int) substr($gstNumber, 0, 2);
        if ($stateCode < 1 || $stateCode > 38) {
            return false;
        }
        
        if (substr($gstNumber, 13, 1) !== 'Z') {
            return false;
        }
        
        return true;
    }
}

