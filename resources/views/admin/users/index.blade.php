@extends('admin.layouts.app')

@section('title', ($status == 'approved' ? 'Approved' : 'Pending') . ' Users (Agents)')
@section('page-title', ($status == 'approved' ? 'Approved' : 'Pending') . ' Users (Agents)')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                {{ $status == 'approved' ? 'Approved' : 'Pending' }} Users (Agents)
                @if($status == 'pending')
                    <span style="font-size: 14px; font-weight: normal; color: #718096;">({{ $pendingCount }} total)</span>
                @else
                    <span style="font-size: 14px; font-weight: normal; color: #718096;">({{ $approvedCount }} total - Active & Inactive)</span>
                @endif
            </h2>
            <div style="display: flex; gap: 12px; align-items: center;">
                <!-- Add Agent Button -->
                <a href="{{ route('admin.users.create', ['status' => $status]) }}" 
                   style="padding: 8px 16px; background: #48bb78; color: white; border: none; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
                    <span>+</span> Add Agent
                </a>
                <!-- Search Form -->
                <form action="{{ route('admin.users.index') }}" method="GET" style="display: flex; gap: 8px;">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search users..." 
                           style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px; width: 250px;">
                    <button type="submit" 
                            style="padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Search
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.users.index', ['status' => $status]) }}" 
                           style="padding: 8px 16px; background: #e2e8f0; color: #2d3748; border-radius: 6px; text-decoration: none;">
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        @if($users->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Agency</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Tax Number</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Profile</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>
                                @if($user->image)
                                    <img src="{{ \App\Services\ImageService::getUrl($user->image) }}" 
                                         alt="{{ $user->name }}" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                @else
                                    <div style="width: 50px; height: 50px; background: #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #718096;">
                                        👤
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $user->name }}</strong>
                            </td>
                            <td>{{ $user->agency_name ?? 'N/A' }}</td>
                            <td>{{ $user->email ?? 'N/A' }}</td>
                            <td>{{ $user->phone ?? 'N/A' }}</td>
                            <td>{{ $user->tax_number ?? 'N/A' }}</td>
                            <td>
                                @if($user->city && $user->state)
                                    {{ $user->city }}, {{ $user->state }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($user->status === 'inactive')
                                    <span class="badge badge-danger">Inactive</span>
                                @else
                                    <span class="badge" style="background: #fbbf24; color: white;">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($user->profile_completed_at)
                                    <span class="badge badge-success">Complete</span>
                                @else
                                    <span class="badge" style="background: #fbbf24; color: white;">Incomplete</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="{{ route('admin.users.edit', ['user' => $user->id, 'status' => $status]) }}" 
                                       style="padding: 6px 12px; background: #4299e1; color: white; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" 
                                          method="POST" 
                                          style="display: inline;"
                                          onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                style="padding: 6px 12px; background: #e53e3e; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top: 20px; display: flex; justify-content: center;">
                {{ $users->appends(['status' => $status, 'search' => request('search')])->links() }}
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <p>No users found.</p>
            </div>
        @endif
    </div>
@endsection

