<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Upload an image and return the stored path
     * 
     * @param UploadedFile $file The uploaded file
     * @param string $folder The folder name (e.g., 'agents', 'services', 'products')
     * @param string|null $subFolder Optional subfolder (e.g., user ID, service ID)
     * @param int $maxSize Maximum file size in KB (default: 2048 = 2MB)
     * @return array Returns ['path' => stored_path, 'url' => accessible_url]
     */
    public static function upload(UploadedFile $file, string $folder, ?string $subFolder = null, int $maxSize = 2048): array
    {
        // Ensure base storage directory exists
        $baseStoragePath = storage_path('app/public');
        if (!is_dir($baseStoragePath)) {
            if (!@mkdir($baseStoragePath, 0755, true)) {
                throw new \Exception("Base storage directory does not exist and could not be created: {$baseStoragePath}. Please create it manually or check permissions.");
            }
        }

        // Validate file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid image type. Allowed types: JPEG, PNG, GIF, WEBP');
        }

        // Validate file size (convert KB to bytes)
        if ($file->getSize() > ($maxSize * 1024)) {
            throw new \Exception("Image size exceeds maximum allowed size of {$maxSize}KB");
        }

        // Sanitize filename
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Remove special characters and spaces, keep only alphanumeric, dots, dashes, underscores
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nameWithoutExtension);
        $sanitizedName = Str::limit($sanitizedName, 100, ''); // Limit length
        
        // Generate unique filename
        $filename = time() . '_' . $sanitizedName . '.' . $extension;

        // Build storage path
        $storagePath = $folder;
        if ($subFolder) {
            $storagePath .= '/' . $subFolder;
        }
        $storagePath .= '/' . $filename;

        // Build directory path
        $directoryPath = $folder;
        if ($subFolder) {
            $directoryPath .= '/' . $subFolder;
        }
        
        // Create directory using Storage facade (Laravel's recommended way)
        // This works better on shared hosting like Hostinger
        try {
            if (!Storage::disk('public')->exists($directoryPath)) {
                Storage::disk('public')->makeDirectory($directoryPath);
            }
        } catch (\Exception $e) {
            // If Storage facade fails, try direct mkdir as fallback
            $fullDirectoryPath = storage_path('app/public/' . $directoryPath);
            if (!is_dir($fullDirectoryPath)) {
                if (!@mkdir($fullDirectoryPath, 0755, true)) {
                    throw new \Exception("Failed to create directory: {$directoryPath}. Error: " . $e->getMessage() . ". Please ensure storage/app/public exists and has write permissions (755).");
                }
            }
        }

        // Store file in public disk - use put() with full path
        // Build the full storage path
        $fullStoragePath = $directoryPath . '/' . $filename;
        
        try {
            // Get file contents and store it
            $fileContents = file_get_contents($file->getRealPath());
            
            // Store the file
            $stored = Storage::disk('public')->put($fullStoragePath, $fileContents);
            
            if (!$stored) {
                throw new \Exception("File storage returned false. Check permissions.");
            }
            
            // Verify file was actually saved
            if (!Storage::disk('public')->exists($fullStoragePath)) {
                throw new \Exception("File was not saved. Verification failed.");
            }
            
            $storedPath = $fullStoragePath;
            
        } catch (\Exception $e) {
            // Fallback: Try using putFileAs method
            try {
                $storedPath = Storage::disk('public')->putFileAs(
                    $directoryPath,
                    $file,
                    $filename
                );
                
                // Verify file exists
                if (!Storage::disk('public')->exists($storedPath)) {
                    throw new \Exception("File was not saved using putFileAs method.");
                }
            } catch (\Exception $e2) {
                // Last resort: Try direct file_put_contents
                $fullPath = storage_path('app/public/' . $fullStoragePath);
                $uploaded = @file_put_contents($fullPath, file_get_contents($file->getRealPath()));
                
                if ($uploaded === false || !file_exists($fullPath)) {
                    throw new \Exception("All storage methods failed. Original error: " . $e->getMessage() . " | Fallback error: " . $e2->getMessage());
                }
                
                $storedPath = $fullStoragePath;
            }
        }

        // Verify the stored path is correct
        if (empty($storedPath)) {
            throw new \Exception("File was stored but path is empty.");
        }
        
        // Ensure path doesn't have 'public/' prefix (Storage adds it sometimes)
        $storedPath = str_replace('public/', '', $storedPath);
        
        // Final verification - check file actually exists
        $finalPath = storage_path('app/public/' . $storedPath);
        if (!file_exists($finalPath)) {
            throw new \Exception("File verification failed. Path: {$storedPath} | Full path: {$finalPath}");
        }

        // Generate accessible URL
        $url = self::getUrl($storedPath);

        return [
            'path' => $storedPath,
            'url' => $url,
            'filename' => $filename,
        ];
    }

    /**
     * Delete an image by path
     * 
     * @param string|null $path The stored path
     * @return bool
     */
    public static function delete(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        // Remove 'public/' prefix if present
        $path = str_replace('public/', '', $path);

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Get accessible URL for an image path
     * 
     * @param string|null $path The stored path
     * @return string|null
     */
    public static function getUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Remove 'public/' prefix if present
        $path = str_replace('public/', '', $path);

        // Generate URL using APP_URL
        $baseUrl = rtrim(config('app.url'), '/');
        
        // Split path into segments
        $pathParts = explode('/', $path);
        
        // Encode each segment separately, keeping slashes readable
        // This ensures: agents/2/filename with spaces.png 
        // becomes: agents/2/filename%20with%20spaces.png
        $encodedParts = [];
        foreach ($pathParts as $part) {
            // Encode each segment (filename/folder) but keep slashes between them
            // rawurlencode handles spaces and special chars but we'll handle it per segment
            $encodedParts[] = rawurlencode($part);
        }
        
        // Join with unencoded slashes to keep URL readable
        $cleanPath = implode('/', $encodedParts);
        
        // Use the image serving route
        return $baseUrl . '/api/images/' . $cleanPath;
    }

    /**
     * Update image (delete old and upload new)
     * 
     * @param UploadedFile $file The new uploaded file
     * @param string|null $oldPath The old image path to delete
     * @param string $folder The folder name
     * @param string|null $subFolder Optional subfolder
     * @param int $maxSize Maximum file size in KB
     * @return array Returns ['path' => stored_path, 'url' => accessible_url]
     */
    public static function update(UploadedFile $file, ?string $oldPath, string $folder, ?string $subFolder = null, int $maxSize = 2048): array
    {
        // Delete old image
        if ($oldPath) {
            self::delete($oldPath);
        }

        // Upload new image
        return self::upload($file, $folder, $subFolder, $maxSize);
    }

    /**
     * Check if image exists
     * 
     * @param string|null $path The stored path
     * @return bool
     */
    public static function exists(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        $path = str_replace('public/', '', $path);
        return Storage::disk('public')->exists($path);
    }

    /**
     * Get image info
     * 
     * @param string|null $path The stored path
     * @return array|null Returns ['size' => bytes, 'mime' => mime_type, 'exists' => bool]
     */
    public static function getInfo(?string $path): ?array
    {
        if (empty($path)) {
            return null;
        }

        $path = str_replace('public/', '', $path);

        if (!Storage::disk('public')->exists($path)) {
            return [
                'exists' => false,
                'size' => 0,
                'mime' => null,
            ];
        }

        $fullPath = Storage::disk('public')->path($path);

        return [
            'exists' => true,
            'size' => filesize($fullPath),
            'mime' => mime_content_type($fullPath),
            'path' => $fullPath,
        ];
    }
}
