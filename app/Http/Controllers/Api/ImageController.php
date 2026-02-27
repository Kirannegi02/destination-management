<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Serve image files
     * This route handles all image requests and ensures they are accessible
     * 
     * @param Request $request
     * @param string $path The image path
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function serve(Request $request, string $path)
    {
        // Decode URL-encoded path (handles both encoded and non-encoded paths)
        $path = urldecode($path);
        
        // Security: prevent directory traversal attacks
        if (strpos($path, '..') !== false || strpos($path, '/') === 0) {
            abort(403, 'Forbidden: Invalid path');
        }
        
        // Normalize path separators (handle both / and \)
        $path = str_replace('\\', '/', $path);

        // Get full file path
        $filePath = storage_path('app/public/' . $path);
        
        // Check if file exists
        if (!file_exists($filePath) || !is_file($filePath)) {
            abort(404, 'Image not found');
        }

        // Get MIME type
        $mimeType = mime_content_type($filePath);
        if (!$mimeType) {
            // Fallback MIME types based on extension
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        }

        // Validate it's actually an image
        if (!str_starts_with($mimeType, 'image/')) {
            abort(403, 'Forbidden: Not an image file');
        }

        // Set cache headers for better performance
        $cacheControl = 'public, max-age=31536000, immutable'; // Cache for 1 year
        
        // Return file with proper headers
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => $cacheControl,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
