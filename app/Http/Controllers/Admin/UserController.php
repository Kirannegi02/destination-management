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
                  ->orWhere('tax_number', 'like', "%{$search}%");
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
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'alternate_phone' => 'nullable|string|max:20',
            'agency_name' => 'required|string|max:255',
            'tax_number' => 'required|string|max:15|unique:users,tax_number',
            'address' => 'required|string',
            'country' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive,pending',
        ]);

        // Validate tax number format
        $taxNumber = strtoupper(trim($validated['tax_number']));
        if (!$this->validateTaxNumber($taxNumber)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['tax_number' => 'Invalid tax number format. Must be 15 characters in format: 2 digits (state code) + 10 alphanumeric (PAN) + 1 digit + Z + 1 digit']);
        }
        $validated['tax_number'] = $taxNumber;

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'alternate_phone' => $validated['alternate_phone'] ?? null,
            'agency_name' => $validated['agency_name'],
            'tax_number' => $validated['tax_number'],
            'address' => $validated['address'],
            'country' => $validated['country'],
            'state' => $validated['state'],
            'city' => $validated['city'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'status' => $validated['status'],
            'profile_completed_at' => now(), // Mark profile as completed since admin is creating it
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $imageData = ImageService::upload(
                    $request->file('image'),
                    'agents',
                    (string) $user->id,
                    2048
                );
                $user->update(['image' => $imageData['path']]);
            } catch (\Exception $e) {
                // If image upload fails, continue without image
                // User can update it later
            }
        }

        // Determine which tab to redirect to based on the status
        $redirectStatus = $validated['status'] === 'pending' ? 'pending' : 'approved';

        return redirect()
            ->route('admin.users.index', ['status' => $redirectStatus])
            ->with('success', 'Agent created successfully.');
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
            'tax_number' => 'required|string|max:15|unique:users,tax_number,' . $user->id,
            'address' => 'required|string',
            'country' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive,pending',
        ]);

        // Validate tax number format
        $taxNumber = strtoupper(trim($validated['tax_number']));
        if (!$this->validateTaxNumber($taxNumber)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['tax_number' => 'Invalid tax number format. Must be 15 characters.']);
        }
        $validated['tax_number'] = $taxNumber;

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
     * Validate tax number format (Indian GST format: 15 chars)
     */
    private function validateTaxNumber($taxNumber)
    {
        $taxNumber = strtoupper(trim($taxNumber));
        
        if (strlen($taxNumber) !== 15) {
            return false;
        }
        
        $pattern = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[0-9A-Z]{1}Z[0-9A-Z]{1}$/';
        
        if (!preg_match($pattern, $taxNumber)) {
            return false;
        }
        
        $stateCode = (int) substr($taxNumber, 0, 2);
        if ($stateCode < 1 || $stateCode > 38) {
            return false;
        }
        
        if (substr($taxNumber, 13, 1) !== 'Z') {
            return false;
        }
        
        return true;
    }
}

