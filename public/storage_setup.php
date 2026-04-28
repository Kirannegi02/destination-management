<?php
/**
 * ONE-TIME storage:link helper.
 * Upload this file to the server's public/ folder, visit it ONCE in the browser,
 * then DELETE it immediately after.
 *
 * URL: https://esmo-2025.org/storage_setup.php
 */

// Simple security token – change this before uploading if you like
define('TOKEN', 'dmc2025setup');

if (($_GET['token'] ?? '') !== TOKEN) {
    http_response_code(403);
    exit('Forbidden. Append ?token=' . TOKEN . ' to the URL.');
}

$publicStoragePath = __DIR__ . '/storage';
$targetPath        = __DIR__ . '/../storage/app/public';

echo '<pre>';
echo "Public storage path : $publicStoragePath\n";
echo "Target (real) path  : $targetPath\n\n";

// Check if target directory exists
if (!is_dir($targetPath)) {
    echo "ERROR: Target directory does not exist: $targetPath\n";
    exit;
}

// Already a working symlink?
if (is_link($publicStoragePath)) {
    echo "Symlink already exists → " . readlink($publicStoragePath) . "\n";
    echo "All good! You can delete this file.\n";
    exit;
}

// Path exists but is a real directory (not a symlink)
if (is_dir($publicStoragePath)) {
    echo "WARNING: public/storage is a real directory (not a symlink).\n";
    echo "Cannot auto-replace it. Please remove it manually via File Manager first.\n";
    exit;
}

// Create the symlink
if (symlink($targetPath, $publicStoragePath)) {
    echo "SUCCESS: Symlink created.\n";
    echo "public/storage → $targetPath\n\n";
    echo "Test a file URL and then DELETE this file from the server!\n";
} else {
    echo "FAILED: Could not create symlink.\n";
    echo "Please run  php artisan storage:link  via SSH or Hostinger terminal.\n";
}

echo '</pre>';
