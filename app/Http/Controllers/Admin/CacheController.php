<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class CacheController extends Controller
{
    /**
     * Clear all Laravel caches.
     */
    public function clearAll(Request $request)
    {
        try {
            $cleared = [];
            
            // Clear config cache
            Artisan::call('config:clear');
            $cleared[] = 'Config cache';
            
            // Clear application cache
            Artisan::call('cache:clear');
            $cleared[] = 'Application cache';
            
            // Clear route cache
            Artisan::call('route:clear');
            $cleared[] = 'Route cache';
            
            // Clear view cache
            Artisan::call('view:clear');
            $cleared[] = 'View cache';
            
            // Clear compiled views manually (in case artisan doesn't work)
            $viewsPath = storage_path('framework/views');
            if (File::exists($viewsPath)) {
                $files = File::glob($viewsPath . '/*.php');
                foreach ($files as $file) {
                    File::delete($file);
                }
                $cleared[] = 'Compiled views (' . count($files) . ' files)';
            }
            
            // Clear cache data folder
            $cachePath = storage_path('framework/cache/data');
            if (File::exists($cachePath)) {
                $files = File::glob($cachePath . '/*');
                $count = 0;
                foreach ($files as $file) {
                    if (File::isFile($file)) {
                        File::delete($file);
                        $count++;
                    }
                }
                if ($count > 0) {
                    $cleared[] = 'Cache data (' . $count . ' files)';
                }
            }
            
            // Clear bootstrap cache files
            $configCache = base_path('bootstrap/cache/config.php');
            if (File::exists($configCache)) {
                File::delete($configCache);
                $cleared[] = 'Bootstrap config cache';
            }
            
            $routeCache = base_path('bootstrap/cache/routes.php');
            if (File::exists($routeCache)) {
                File::delete($routeCache);
                $cleared[] = 'Bootstrap route cache';
            }
            
            $servicesCache = base_path('bootstrap/cache/services.php');
            if (File::exists($servicesCache)) {
                File::delete($servicesCache);
                $cleared[] = 'Bootstrap services cache';
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'All caches cleared successfully',
                    'cleared' => $cleared
                ]);
            }
            
            return redirect()->back()->with('success', 'All caches cleared successfully! Cleared: ' . implode(', ', $cleared));
            
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error clearing cache: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error clearing cache: ' . $e->getMessage());
        }
    }
    
    /**
     * Clear specific cache type.
     */
    public function clear(Request $request, $type)
    {
        try {
            $cleared = [];
            
            switch ($type) {
                case 'config':
                    Artisan::call('config:clear');
                    $configCache = base_path('bootstrap/cache/config.php');
                    if (File::exists($configCache)) {
                        File::delete($configCache);
                    }
                    $cleared[] = 'Config cache';
                    break;
                    
                case 'cache':
                    Artisan::call('cache:clear');
                    $cachePath = storage_path('framework/cache/data');
                    if (File::exists($cachePath)) {
                        $files = File::glob($cachePath . '/*');
                        foreach ($files as $file) {
                            if (File::isFile($file)) {
                                File::delete($file);
                            }
                        }
                    }
                    $cleared[] = 'Application cache';
                    break;
                    
                case 'route':
                    Artisan::call('route:clear');
                    $routeCache = base_path('bootstrap/cache/routes.php');
                    if (File::exists($routeCache)) {
                        File::delete($routeCache);
                    }
                    $cleared[] = 'Route cache';
                    break;
                    
                case 'view':
                    Artisan::call('view:clear');
                    $viewsPath = storage_path('framework/views');
                    if (File::exists($viewsPath)) {
                        $files = File::glob($viewsPath . '/*.php');
                        foreach ($files as $file) {
                            File::delete($file);
                        }
                    }
                    $cleared[] = 'View cache';
                    break;
                    
                default:
                    return redirect()->back()->with('error', 'Invalid cache type');
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => ucfirst($type) . ' cache cleared successfully',
                    'cleared' => $cleared
                ]);
            }
            
            return redirect()->back()->with('success', ucfirst($type) . ' cache cleared successfully!');
            
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error clearing cache: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error clearing cache: ' . $e->getMessage());
        }
    }
}
