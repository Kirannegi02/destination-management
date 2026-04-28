<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MediaLibraryController extends Controller
{
    private const FOLDER       = 'media-library';
    private const VIDEO_FOLDER = 'media-library/videos';

    private const MAX_IMAGE_KB = 5120;           // 5 MB
    private const MAX_VIDEO_KB = 102400;         // 100 MB

    private const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const VIDEO_EXTS = ['mp4', 'mov', 'webm', 'avi', 'mkv'];

    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $disk   = Storage::disk('public');
        $images = [];
        $videos = [];

        if ($disk->exists(self::FOLDER)) {
            $allFiles = collect($disk->allFiles(self::FOLDER))
                ->sortByDesc(fn (string $p) => $disk->lastModified($p));

            foreach ($allFiles as $path) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                if (in_array($ext, self::IMAGE_EXTS, true)) {
                    $images[] = [
                        'path'     => $path,
                        'url'      => ImageService::getUrl($path),
                        'filename' => basename($path),
                        'size_kb'  => (int) round($disk->size($path) / 1024),
                    ];
                } elseif (in_array($ext, self::VIDEO_EXTS, true)) {
                    $videos[] = [
                        'path'     => $path,
                        'url'      => Storage::disk('public')->url($path),
                        'filename' => basename($path),
                        'size_mb'  => round($disk->size($path) / (1024 * 1024), 1),
                        'ext'      => $ext,
                    ];
                }
            }
        }

        return view('admin.media-library.index', [
            'images' => $images,
            'videos' => $videos,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Image upload
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'files'    => ['required', 'array', 'min:1'],
            'files.*'  => ['file', 'image', 'max:' . self::MAX_IMAGE_KB],
        ], [
            'files.required' => 'Select at least one image.',
            'files.*.image'  => 'Each file must be an image (JPEG, PNG, GIF, or WebP).',
            'files.*.max'    => 'Each image may not be larger than ' . self::MAX_IMAGE_KB . ' KB.',
        ]);

        $uploaded = [];
        $errors   = [];

        foreach ($request->file('files', []) as $file) {
            if (!$file || !$file->isValid()) {
                $errors[] = 'One of the files failed to upload.';
                continue;
            }
            try {
                $result     = ImageService::upload($file, self::FOLDER, null, self::MAX_IMAGE_KB);
                $uploaded[] = [
                    'path'     => $result['path'],
                    'url'      => $result['url'],
                    'filename' => $result['filename'],
                ];
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        return $this->redirectAfterUpload($uploaded, $errors, 'image');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Video upload
    // ─────────────────────────────────────────────────────────────────────────

    public function storeVideo(Request $request): RedirectResponse
    {
        $request->validate([
            'videos'   => ['required', 'array', 'min:1'],
            'videos.*' => [
                'file',
                'mimes:mp4,mov,webm,avi,mkv',
                'max:' . self::MAX_VIDEO_KB,
            ],
        ], [
            'videos.required' => 'Select at least one video file.',
            'videos.*.mimes'  => 'Each file must be a video (MP4, MOV, WebM, AVI, or MKV).',
            'videos.*.max'    => 'Each video may not be larger than ' . (self::MAX_VIDEO_KB / 1024) . ' MB.',
        ]);

        $disk     = Storage::disk('public');
        $uploaded = [];
        $errors   = [];

        foreach ($request->file('videos', []) as $file) {
            if (!$file || !$file->isValid()) {
                $errors[] = 'One of the files failed to upload.';
                continue;
            }

            try {
                $ext      = strtolower($file->getClientOriginalExtension());
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $path     = self::VIDEO_FOLDER . '/' . $filename;

                $disk->put($path, file_get_contents($file->getRealPath()));

                $url = $disk->url($path);

                $uploaded[] = [
                    'path'     => $path,
                    'url'      => $url,
                    'filename' => $filename,
                ];
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        return $this->redirectAfterUpload($uploaded, $errors, 'video');
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function redirectAfterUpload(array $uploaded, array $errors, string $type): RedirectResponse
    {
        $redirect = redirect()->route('admin.media-library.index');

        $label = $type === 'video' ? 'video(s)' : 'image(s)';

        if (!empty($uploaded)) {
            $redirect->with('success', count($uploaded) . " {$label} uploaded successfully.");
            $redirect->with('uploaded_' . $type, $uploaded);
        }

        if (!empty($errors)) {
            $redirect->with('error', implode(' ', $errors));
        }

        if (empty($uploaded) && empty($errors)) {
            $redirect->with('error', 'No files were processed.');
        }

        return $redirect;
    }
}
