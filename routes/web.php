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
        Route::get('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])
            ->whereNumber('user')
            ->name('users.show');
        
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

        // Sightseeing routes
        Route::resource('sightseeings', \App\Http\Controllers\Admin\SightseeingController::class)
            ->whereNumber('sightseeing');
        Route::get('/sightseeings', [\App\Http\Controllers\Admin\SightseeingController::class, 'index'])->name('sightseeings.index');
        Route::get('/sightseeings/{sightseeing}', [\App\Http\Controllers\Admin\SightseeingController::class, 'show'])
            ->whereNumber('sightseeing')
            ->name('sightseeings.show');
        Route::prefix('sightseeings')->name('sightseeings.')->group(function () {
            Route::get('/export', [\App\Http\Controllers\Admin\SightseeingController::class, 'export'])->name('export');
            Route::get('/export/page', [\App\Http\Controllers\Admin\SightseeingController::class, 'exportPage'])->name('export.page');
            Route::get('/import', [\App\Http\Controllers\Admin\SightseeingController::class, 'importForm'])->name('import.form');
            Route::post('/import', [\App\Http\Controllers\Admin\SightseeingController::class, 'import'])->name('import');
            Route::get('/import/sample', [\App\Http\Controllers\Admin\SightseeingController::class, 'sample'])->name('import.sample');
        });

        // Guide bookings
        Route::get('/guide-bookings', [\App\Http\Controllers\Admin\GuideBookingController::class, 'index'])->name('guide_bookings.index');
        Route::get('/guide-bookings/{id}', [\App\Http\Controllers\Admin\GuideBookingController::class, 'show'])
            ->whereNumber('id')
            ->name('guide-bookings.show');
        Route::post('/guide-bookings/{id}/status', [\App\Http\Controllers\Admin\GuideBookingController::class, 'updateStatus'])
            ->whereNumber('id')
            ->name('guide-bookings.status');

        // Sightseeing bookings
        Route::get('/sightseeing-bookings', [\App\Http\Controllers\Admin\SightseeingBookingController::class, 'index'])->name('sightseeing-bookings.index');
        Route::get('/sightseeing-bookings/{id}', [\App\Http\Controllers\Admin\SightseeingBookingController::class, 'show'])->name('sightseeing-bookings.show')->whereNumber('id');
        Route::post('/sightseeing-bookings/{id}/status', [\App\Http\Controllers\Admin\SightseeingBookingController::class, 'updateStatus'])->name('sightseeing-bookings.status')->whereNumber('id');

        // Transport routes (zone + vehicle type + price per km / day)
        Route::post('/transports/reverse-geocode', [\App\Http\Controllers\Admin\TransportController::class, 'reverseGeocode'])
            ->name('transports.reverse-geocode');
        Route::post('/transports/forward-geocode', [\App\Http\Controllers\Admin\TransportController::class, 'forwardGeocode'])
            ->name('transports.forward-geocode');
        Route::post('/transports/suggest-zone-cities', [\App\Http\Controllers\Admin\TransportController::class, 'suggestCitiesFromPolygon'])
            ->name('transports.suggest-zone-cities');

        Route::prefix('transports')->name('transports.')->group(function () {
            Route::get('/export', [\App\Http\Controllers\Admin\TransportController::class, 'export'])->name('export');
            Route::get('/export/page', [\App\Http\Controllers\Admin\TransportController::class, 'exportPage'])->name('export.page');
            Route::get('/import', [\App\Http\Controllers\Admin\TransportController::class, 'importForm'])->name('import.form');
            Route::post('/import', [\App\Http\Controllers\Admin\TransportController::class, 'import'])->name('import');
            Route::get('/import/sample', [\App\Http\Controllers\Admin\TransportController::class, 'sample'])->name('import.sample');
        });
        Route::resource('transports', \App\Http\Controllers\Admin\TransportController::class);
        Route::get('/transports', [\App\Http\Controllers\Admin\TransportController::class, 'index'])->name('transports.index');
        Route::get('/transports/{transport}', [\App\Http\Controllers\Admin\TransportController::class, 'show'])->name('transports.show');

        Route::get('/transport-bookings', [\App\Http\Controllers\Admin\TransportBookingController::class, 'index'])->name('transport-bookings.index');
        Route::get('/transport-bookings/{id}', [\App\Http\Controllers\Admin\TransportBookingController::class, 'show'])->name('transport-bookings.show')->whereNumber('id');
        Route::post('/transport-bookings/{id}/status', [\App\Http\Controllers\Admin\TransportBookingController::class, 'updateStatus'])->name('transport-bookings.status')->whereNumber('id');

        // Vehicle routes (vehicle types - separate listing/add/edit)
        Route::prefix('vehicles')->name('vehicles.')->group(function () {
            Route::get('/export', [\App\Http\Controllers\Admin\VehicleController::class, 'export'])->name('export');
            Route::get('/export/page', [\App\Http\Controllers\Admin\VehicleController::class, 'exportPage'])->name('export.page');
            Route::get('/import', [\App\Http\Controllers\Admin\VehicleController::class, 'importForm'])->name('import.form');
            Route::post('/import', [\App\Http\Controllers\Admin\VehicleController::class, 'import'])->name('import');
            Route::get('/import/sample', [\App\Http\Controllers\Admin\VehicleController::class, 'sample'])->name('import.sample');
        });
        Route::resource('vehicles', \App\Http\Controllers\Admin\VehicleController::class);
        Route::get('/vehicles', [\App\Http\Controllers\Admin\VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/{vehicle}', [\App\Http\Controllers\Admin\VehicleController::class, 'show'])->name('vehicles.show');

        // Global meal templates (shared menu for all restaurants)
        Route::prefix('meal-templates')->name('meal-templates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\MealTemplateController::class, 'index'])->name('index');
            Route::get('/{mealTemplate}/edit', [\App\Http\Controllers\Admin\MealTemplateController::class, 'edit'])->name('edit');
            Route::put('/{mealTemplate}', [\App\Http\Controllers\Admin\MealTemplateController::class, 'update'])->name('update');
        });

        // Meal routes (import/export before resource)
        Route::prefix('meals')->name('meals.')->group(function () {
            Route::get('/export', [\App\Http\Controllers\Admin\MealController::class, 'export'])->name('export');
            Route::get('/export/page', [\App\Http\Controllers\Admin\MealController::class, 'exportPage'])->name('export.page');
            Route::get('/import', [\App\Http\Controllers\Admin\MealController::class, 'importForm'])->name('import.form');
            Route::post('/import', [\App\Http\Controllers\Admin\MealController::class, 'import'])->name('import');
            Route::get('/import/sample', [\App\Http\Controllers\Admin\MealController::class, 'sample'])->name('import.sample');
        });
        Route::resource('meals', \App\Http\Controllers\Admin\MealController::class);
        Route::get('/meals', [\App\Http\Controllers\Admin\MealController::class, 'index'])->name('meals.index');

        // Souvenir routes
        Route::prefix('souvenirs')->name('souvenirs.')->group(function () {
            Route::get('/export', [\App\Http\Controllers\Admin\SouvenirController::class, 'export'])->name('export');
            Route::get('/export/page', [\App\Http\Controllers\Admin\SouvenirController::class, 'exportPage'])->name('export.page');
            Route::get('/import', [\App\Http\Controllers\Admin\SouvenirController::class, 'importForm'])->name('import.form');
            Route::post('/import', [\App\Http\Controllers\Admin\SouvenirController::class, 'import'])->name('import');
            Route::get('/import/sample', [\App\Http\Controllers\Admin\SouvenirController::class, 'sample'])->name('import.sample');
        });
        Route::resource('souvenirs', \App\Http\Controllers\Admin\SouvenirController::class);
        Route::get('/souvenirs', [\App\Http\Controllers\Admin\SouvenirController::class, 'index'])->name('souvenirs.index');
        Route::get('/souvenirs/{souvenir}', [\App\Http\Controllers\Admin\SouvenirController::class, 'show'])->name('souvenirs.show');

        // Souvenir orders
        Route::get('/souvenir-orders', [\App\Http\Controllers\Admin\SouvenirOrderController::class, 'index'])->name('souvenir-orders.index');
        Route::get('/souvenir-orders/{id}', [\App\Http\Controllers\Admin\SouvenirOrderController::class, 'show'])->name('souvenir-orders.show')->whereNumber('id');
        Route::get('/souvenir-orders/{id}/invoice', [\App\Http\Controllers\Admin\SouvenirOrderController::class, 'invoice'])->name('souvenir-orders.invoice')->whereNumber('id');
        Route::post('/souvenir-orders/{id}/status', [\App\Http\Controllers\Admin\SouvenirOrderController::class, 'updateStatus'])->name('souvenir-orders.status')->whereNumber('id');

        // Booking routes
        Route::resource('bookings', \App\Http\Controllers\Admin\BookingController::class)->only(['index', 'show']);
        Route::post('/bookings/{booking}/status', [\App\Http\Controllers\Admin\BookingController::class, 'updateStatus'])->name('bookings.status');

        // Media library (multi-upload + copyable paths/URLs)
        Route::get('/media-library', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'index'])->name('media-library.index');
        Route::post('/media-library', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'store'])->name('media-library.store');
        Route::post('/media-library/videos', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'storeVideo'])->name('media-library.store-video');

        // Admin profile
        Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password');
        
        // Settings routes
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/smtp', [\App\Http\Controllers\Admin\SettingController::class, 'updateSmtp'])->name('settings.smtp.update');
        Route::post('/settings/firebase', [\App\Http\Controllers\Admin\SettingController::class, 'updateFirebase'])->name('settings.firebase.update');
        Route::post('/settings/razorpay', [\App\Http\Controllers\Admin\SettingController::class, 'updateRazorpay'])->name('settings.razorpay.update');
        Route::post('/settings/guide-cms', [\App\Http\Controllers\Admin\SettingController::class, 'updateGuideCms'])->name('settings.guide_cms.update');
        Route::post('/settings/souvenir-shipping', [\App\Http\Controllers\Admin\SettingController::class, 'updateSouvenirShipping'])->name('settings.souvenir_shipping.update');
        
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
