<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public cache clear route (with secret token for emergency use)
// Usage: /clear-cache?token=YOUR_SECRET_TOKEN
// NOTE: This may still hit database due to session middleware
// For guaranteed bypass, use: /clear-cache.php?token=YOUR_SECRET_TOKEN (standalone file)
Route::get('/clear-cache', function (Request $request) {
    $secretToken = env('CACHE_CLEAR_TOKEN', '1234567890');
    $providedToken = $request->get('token');
    
    if ($providedToken !== $secretToken) {
        return response()->json(['error' => 'Invalid token'], 403);
    }
    
    try {
        $cleared = [];
        
        // Clear config cache
        $configFile = base_path('bootstrap/cache/config.php');
        if (file_exists($configFile)) {
            unlink($configFile);
            $cleared[] = 'Config cache';
        }
        
        // Clear route cache
        $routeFile = base_path('bootstrap/cache/routes.php');
        if (file_exists($routeFile)) {
            unlink($routeFile);
            $cleared[] = 'Route cache';
        }
        
        // Clear services cache
        $servicesFile = base_path('bootstrap/cache/services.php');
        if (file_exists($servicesFile)) {
            unlink($servicesFile);
            $cleared[] = 'Services cache';
        }
        
        // Clear application cache files
        $cacheDir = storage_path('framework/cache/data');
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            $count = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
            if ($count > 0) {
                $cleared[] = "Application cache ($count files)";
            }
        }
        
        // Clear compiled views
        $viewsDir = storage_path('framework/views');
        if (is_dir($viewsDir)) {
            $files = glob($viewsDir . '/*.php');
            $count = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
            if ($count > 0) {
                $cleared[] = "Compiled views ($count files)";
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'All caches cleared successfully!',
            'cleared' => $cleared
        ], 200, [], JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error clearing cache: ' . $e->getMessage()
        ], 500, [], JSON_PRETTY_PRINT);
    }
})->name('cache.clear.public');

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Admin Authentication Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Login routes (accessible without authentication)
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Protected admin routes
    Route::middleware(['admin.auth'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // Service routes
        Route::resource('services', \App\Http\Controllers\Admin\ServiceController::class)->except(['show']);
        Route::get('/services', [\App\Http\Controllers\Admin\ServiceController::class, 'index'])->name('services.index');
        
        // User (Agents) routes
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['show']);
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        
        // Restaurant routes
        Route::prefix('restaurants')->name('restaurants.')->group(function () {
            Route::get('/export', [\App\Http\Controllers\Admin\RestaurantController::class, 'export'])->name('export');
            Route::get('/export/page', [\App\Http\Controllers\Admin\RestaurantController::class, 'exportPage'])->name('export.page');
            Route::get('/import', [\App\Http\Controllers\Admin\RestaurantController::class, 'importForm'])->name('import.form');
            Route::post('/import', [\App\Http\Controllers\Admin\RestaurantController::class, 'import'])->name('import');
            Route::get('/import/sample', [\App\Http\Controllers\Admin\RestaurantController::class, 'sample'])->name('import.sample');
        });
        Route::resource('restaurants', \App\Http\Controllers\Admin\RestaurantController::class);
        Route::get('/restaurants', [\App\Http\Controllers\Admin\RestaurantController::class, 'index'])->name('restaurants.index');
        Route::get('/restaurants/{restaurant}', [\App\Http\Controllers\Admin\RestaurantController::class, 'show'])->name('restaurants.show');

        // Guide routes
        Route::prefix('guides')->name('guides.')->group(function () {
            Route::get('/export', [\App\Http\Controllers\Admin\GuideController::class, 'export'])->name('export');
            Route::get('/export/page', [\App\Http\Controllers\Admin\GuideController::class, 'exportPage'])->name('export.page');
            Route::get('/import', [\App\Http\Controllers\Admin\GuideController::class, 'importForm'])->name('import.form');
            Route::post('/import', [\App\Http\Controllers\Admin\GuideController::class, 'import'])->name('import');
            Route::get('/import/sample', [\App\Http\Controllers\Admin\GuideController::class, 'sample'])->name('import.sample');
        });
        Route::resource('guides', \App\Http\Controllers\Admin\GuideController::class);
        Route::get('/guides', [\App\Http\Controllers\Admin\GuideController::class, 'index'])->name('guides.index');
        Route::get('/guides/{guide}', [\App\Http\Controllers\Admin\GuideController::class, 'show'])->name('guides.show');

        // Meal routes
        Route::resource('meals', \App\Http\Controllers\Admin\MealController::class);
        Route::get('/meals', [\App\Http\Controllers\Admin\MealController::class, 'index'])->name('meals.index');

        // Booking routes
        Route::resource('bookings', \App\Http\Controllers\Admin\BookingController::class)->only(['index', 'show']);
        Route::post('/bookings/{booking}/status', [\App\Http\Controllers\Admin\BookingController::class, 'updateStatus'])->name('bookings.status');

        // Admin profile
        Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password');
        
        // Settings routes
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/smtp', [\App\Http\Controllers\Admin\SettingController::class, 'updateSmtp'])->name('settings.smtp.update');
        Route::post('/settings/firebase', [\App\Http\Controllers\Admin\SettingController::class, 'updateFirebase'])->name('settings.firebase.update');
        Route::post('/settings/razorpay', [\App\Http\Controllers\Admin\SettingController::class, 'updateRazorpay'])->name('settings.razorpay.update');
        
        // Cache management routes
        Route::post('/cache/clear', function () {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            
            // Delete bootstrap cache files
            $files = ['bootstrap/cache/config.php', 'bootstrap/cache/routes.php', 'bootstrap/cache/services.php'];
            foreach ($files as $file) {
                $path = base_path($file);
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            
            return redirect()->back()->with('success', 'All caches cleared successfully!');
        })->name('cache.clear.all');
        
        Route::post('/cache/clear/{type}', function ($type) {
            $cleared = [];
            
            switch ($type) {
                case 'config':
                    \Illuminate\Support\Facades\Artisan::call('config:clear');
                    $path = base_path('bootstrap/cache/config.php');
                    if (file_exists($path)) unlink($path);
                    $cleared[] = 'Config cache';
                    break;
                case 'cache':
                    \Illuminate\Support\Facades\Artisan::call('cache:clear');
                    $cleared[] = 'Application cache';
                    break;
                case 'route':
                    \Illuminate\Support\Facades\Artisan::call('route:clear');
                    $path = base_path('bootstrap/cache/routes.php');
                    if (file_exists($path)) unlink($path);
                    $cleared[] = 'Route cache';
                    break;
                case 'view':
                    \Illuminate\Support\Facades\Artisan::call('view:clear');
                    $cleared[] = 'View cache';
                    break;
                default:
                    return redirect()->back()->with('error', 'Invalid cache type');
            }
            
            return redirect()->back()->with('success', ucfirst($type) . ' cache cleared successfully!');
        })->name('cache.clear');
    });
});
