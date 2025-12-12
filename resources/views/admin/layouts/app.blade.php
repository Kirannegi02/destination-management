<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - DMC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f7fafc;
            color: #2d3748;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header .logo {
            font-size: 28px;
            font-weight: bold;
            color: white;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: block;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: #60a5fa;
        }

        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: #60a5fa;
        }

        .menu-item.has-submenu {
            position: relative;
        }

        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background-color: rgba(0, 0, 0, 0.2);
        }

        .submenu.expanded {
            max-height: 200px;
        }

        .submenu-item {
            display: block;
            padding: 10px 20px 10px 50px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            font-size: 14px;
        }

        .submenu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: #60a5fa;
        }

        .submenu-item.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: #60a5fa;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 24px;
            font-weight: 600;
            color: #1a202c;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
        }

        .user-role {
            font-size: 12px;
            color: #718096;
        }

        .btn-logout {
            padding: 8px 16px;
            background: #e53e3e;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .btn-logout:hover {
            background: #c53030;
        }

        /* Content Area */
        .content {
            padding: 30px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .stat-title {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .stat-change {
            font-size: 12px;
            color: #48bb78;
        }

        /* Tables */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            padding: 24px;
            margin-bottom: 30px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .table th {
            font-weight: 600;
            color: #4a5568;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            color: #2d3748;
            font-size: 14px;
        }

        .table tr:hover {
            background-color: #f7fafc;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .badge-warning {
            background-color: #feebc8;
            color: #7c2d12;
        }

        .badge-danger {
            background-color: #fed7d7;
            color: #c53030;
        }

        .badge-info {
            background-color: #bee3f8;
            color: #2c5282;
        }

        /* Notifications */
        .notification-item {
            padding: 16px;
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .notification-text {
            font-size: 14px;
            color: #78350f;
            line-height: 1.6;
        }

        .notification-date {
            font-size: 12px;
            color: #92400e;
            margin-top: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #718096;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">DMC</div>
            </div>
            <nav class="sidebar-menu">
                <a href="{{ route('admin.dashboard') }}" class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    📊 Dashboard
                </a>
                @php
                    $isBookingsActive = request()->routeIs('admin.bookings.*');
                @endphp
                <a href="{{ route('admin.bookings.index') }}" class="menu-item {{ $isBookingsActive ? 'active' : '' }}">
                    📋 Bookings
                </a>
                
                @php
                    $isRestaurantsActive = request()->routeIs('admin.restaurants.*');
                @endphp
                <a href="{{ route('admin.restaurants.index') }}" 
                   class="menu-item {{ $isRestaurantsActive ? 'active' : '' }}">
                    🍽️ Restaurants
                </a>
                
                @php
                    $serviceTypes = [
                        'guide' => ['icon' => '👨‍🏫', 'label' => 'Guides'],
                        'sightseeing' => ['icon' => '🏛️', 'label' => 'Sightseeing'],
                        'transport' => ['icon' => '🚗', 'label' => 'Transport'],
                        'souvenir' => ['icon' => '🎁', 'label' => 'Souvenirs'],
                        'private_venue' => ['icon' => '🏰', 'label' => 'Private Venues'],
                        'catering' => ['icon' => '🍴', 'label' => 'Catering'],
                        'train' => ['icon' => '🚂', 'label' => 'Trains'],
                    ];
                @endphp
                
                @foreach($serviceTypes as $serviceType => $config)
                    @php
                        $isActive = request()->routeIs('admin.services.*') && 
                                   (request()->get('type') == $serviceType || 
                                    (isset($service) && $service->type == $serviceType) ||
                                    (isset($type) && $type == $serviceType));
                    @endphp
                    <a href="{{ route('admin.services.index', ['type' => $serviceType]) }}" 
                       class="menu-item {{ $isActive ? 'active' : '' }}">
                        {{ $config['icon'] }} {{ $config['label'] }}
                    </a>
                @endforeach
                
                <a href="#" class="menu-item">
                    📊 Reports
                </a>
                <a href="#" class="menu-item">
                    🎯 Destinations
                </a>
                @php
                    $isUsersActive = request()->routeIs('admin.users.*');
                    $currentStatus = request('status', 'pending'); // Default to pending
                    $isPending = $currentStatus == 'pending' || !request()->has('status');
                    $isApproved = $currentStatus == 'approved';
                @endphp
                <div class="menu-item has-submenu {{ $isUsersActive ? 'active' : '' }}" 
                     onclick="toggleSubmenu(this)" 
                     style="cursor: pointer;">
                    👥 Users (Agents)
                    <span class="submenu-arrow" style="float: right; margin-top: 2px;">{{ $isUsersActive ? '▼' : '▶' }}</span>
                </div>
                <div class="submenu {{ $isUsersActive ? 'expanded' : '' }}">
                    <a href="{{ route('admin.users.index', ['status' => 'pending']) }}" 
                       class="submenu-item {{ $isPending ? 'active' : '' }}">
                        ⏳ Pending
                    </a>
                    <a href="{{ route('admin.users.index', ['status' => 'approved']) }}" 
                       class="submenu-item {{ $isApproved ? 'active' : '' }}">
                        ✅ Approved
                    </a>
                </div>
                <a href="{{ route('admin.settings.index') }}" class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    ⚙️ Settings
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <h1 class="header-title">@yield('page-title', 'Dashboard')</h1>
                <div class="header-actions">
                    <a href="{{ route('admin.profile.edit') }}" class="user-info" style="text-decoration: none; color: inherit;">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth('admin')->user()->name, 0, 1)) }}
                        </div>
                        <div class="user-details">
                            <div class="user-name">{{ auth('admin')->user()->name }}</div>
                            <div class="user-role">Administrator</div>
                        </div>
                    </a>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-logout">Logout</button>
                    </form>
                </div>
            </header>

            <!-- Content -->
            <main class="content">
                @if(session('success'))
                    <div style="background: #c6f6d5; color: #22543d; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div style="background: #fed7d7; color: #c53030; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
    <script>
        function toggleSubmenu(element) {
            const submenu = element.nextElementSibling;
            const arrow = element.querySelector('.submenu-arrow');
            
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.classList.toggle('expanded');
                if (arrow) {
                    arrow.textContent = submenu.classList.contains('expanded') ? '▼' : '▶';
                }
            }
        }

        // Auto-expand submenu if any sub-item is active
        document.addEventListener('DOMContentLoaded', function() {
            const activeSubItem = document.querySelector('.submenu-item.active');
            if (activeSubItem) {
                const submenu = activeSubItem.closest('.submenu');
                const parentItem = submenu.previousElementSibling;
                if (submenu && parentItem) {
                    submenu.classList.add('expanded');
                    const arrow = parentItem.querySelector('.submenu-arrow');
                    if (arrow) {
                        arrow.textContent = '▼';
                    }
                }
            }
        });
    </script>
</body>
</html>

