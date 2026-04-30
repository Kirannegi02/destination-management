@extends('admin.layouts.app')

@section('title', 'Media Library')
@section('page-title', 'Media Library')

@section('content')
<style>
    .ml-tabs { display: flex; gap: 0; border-bottom: 2px solid #e2e8f0; margin-bottom: 24px; }
    .ml-tab {
        padding: 10px 24px; font-size: 14px; font-weight: 600; cursor: pointer;
        border: none; background: none; color: #718096; border-bottom: 3px solid transparent;
        margin-bottom: -2px; transition: color .15s, border-color .15s;
    }
    .ml-tab.active { color: #667eea; border-bottom-color: #667eea; }
    .ml-tab:hover:not(.active) { color: #4a5568; }

    .ml-panel { display: none; }
    .ml-panel.active { display: block; }

    .upload-card {
        background: white; border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,.05);
        padding: 24px; margin-bottom: 28px;
    }
    .upload-card h2 { font-size: 17px; font-weight: 600; color: #1a202c; margin-bottom: 8px; }
    .upload-card p  { color: #718096; font-size: 13px; margin-bottom: 14px; }
    .upload-dropzone {
        width: 100%; padding: 14px;
        border: 2px dashed #cbd5e0; border-radius: 8px;
        background: #f7fafc; font-size: 13px; cursor: pointer;
    }
    .btn-primary {
        display: inline-block; margin-top: 14px;
        padding: 11px 22px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white; border: none; border-radius: 8px;
        font-weight: 600; cursor: pointer; font-size: 14px;
    }
    .btn-primary:hover { opacity: .93; }

    .media-grid { display: grid; gap: 18px; }
    .media-row {
        display: grid; grid-template-columns: 130px 1fr;
        gap: 18px; background: white; border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,.05); padding: 16px; align-items: start;
    }
    @media (max-width: 640px) { .media-row { grid-template-columns: 1fr; } }

    .media-thumb {
        width: 130px; height: 100px; object-fit: cover;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #f0f4f8;
    }
    .video-thumb {
        width: 130px; height: 100px;
        border-radius: 8px; border: 1px solid #e2e8f0;
        background: #1a202c; display: flex; align-items: center;
        justify-content: center; flex-direction: column; gap: 4px; color: #fff;
        font-size: 11px; font-weight: 600; text-transform: uppercase;
    }
    .video-thumb .play-icon { font-size: 28px; }

    .media-fields label {
        display: block; font-size: 11px; font-weight: 700;
        color: #718096; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 5px;
    }
    .media-fields .field-row { margin-bottom: 10px; }
    .media-fields input[type="text"] {
        width: 100%; padding: 9px 11px; border: 2px solid #e2e8f0;
        border-radius: 8px; font-size: 12px; font-family: ui-monospace, monospace;
        background: #f7fafc;
    }
    .copy-actions { margin-top: 5px; display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-copy {
        padding: 7px 13px; font-size: 12px; border-radius: 6px;
        border: 1px solid #e2e8f0; background: #fff; cursor: pointer;
        font-weight: 500; color: #4a5568;
    }
    .btn-copy:hover { background: #edf2f7; }
    .badge-ext {
        display: inline-block; padding: 2px 7px; font-size: 11px; font-weight: 700;
        border-radius: 4px; background: #e2e8f0; color: #4a5568; text-transform: uppercase;
    }
    .empty-state {
        background: white; border-radius: 12px;
        padding: 40px; text-align: center; color: #718096;
        box-shadow: 0 2px 8px rgba(0,0,0,.05);
    }

    .alert { padding: 13px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; }
    .alert-success { background: #f0fff4; color: #276749; border: 1px solid #9ae6b4; }
    .alert-error   { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }
    .alert-info    { background: #ebf8ff; color: #2c5282; border: 1px solid #90cdf4; }
</style>

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif
@if(session('uploaded_image') && count(session('uploaded_image')))
    <div class="alert alert-info">Image(s) just uploaded — copy the URL below.</div>
@endif
@if(session('uploaded_video') && count(session('uploaded_video')))
    <div class="alert alert-info">Video(s) just uploaded — copy the URL below and paste it into the restaurant's Video URL field.</div>
@endif

{{-- Validation errors --}}
@if($errors->any())
    <div class="alert alert-error">
        <ul style="margin:0; padding-left:18px;">
            @foreach($errors->all() as $msg)<li>{{ $msg }}</li>@endforeach
        </ul>
    </div>
@endif

{{-- Tabs --}}
<div class="ml-tabs">
    <button class="ml-tab active" data-tab="images">
        🖼 Images ({{ count($images) }})
    </button>
    <button class="ml-tab" data-tab="videos">
        🎬 Videos ({{ count($videos) }})
    </button>
</div>

{{-- ── IMAGE PANEL ─────────────────────────────────────────────────────────── --}}
<div class="ml-panel active" id="tab-images">

    <div class="upload-card">
        <h2>Upload Images</h2>
        <p>JPEG, PNG, GIF, WebP — up to {{ number_format(5120) }} KB each. Select multiple files at once.</p>
        <form action="{{ route('admin.media-library.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input class="upload-dropzone" type="file"
                   name="files[]"
                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                   multiple required>
            <button type="submit" class="btn-primary">Upload Images</button>
        </form>
    </div>

    @if(count($images) === 0)
        <div class="empty-state">No images uploaded yet.</div>
    @else
        <h3 style="font-size:16px; font-weight:600; margin-bottom:14px; color:#1a202c;">
            Library — {{ count($images) }} image(s)
        </h3>
        <div class="media-grid">
            @foreach ($images as $item)
                <div class="media-row">
                    <div>
                        <img class="media-thumb" src="{{ $item['url'] }}" alt="Media image">
                        <div style="font-size:11px; color:#718096; margin-top:4px; text-align:center;">
                            {{ $item['size_kb'] }} KB
                        </div>
                    </div>
                    <div class="media-fields">
                        <div class="field-row">
                            <label>Public URL — paste into <em>images</em> column in Excel</label>
                            <input type="text" readonly value="{{ $item['url'] }}" id="img-{{ $loop->index }}">
                            <div class="copy-actions">
                                <button type="button" class="btn-copy" data-copy-target="img-{{ $loop->index }}">
                                    Copy URL
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ── VIDEO PANEL ─────────────────────────────────────────────────────────── --}}
<div class="ml-panel" id="tab-videos">

    <div class="upload-card">
        <h2>Upload Videos</h2>
        <p>
            MP4, MOV, WebM, AVI, MKV — up to <strong>100 MB</strong> each.<br>
            After uploading, copy the URL and paste it into the <strong>video</strong> column
            in the restaurant Excel sheet, or into the <em>Video URL</em> field on the restaurant edit page.
        </p>
        <form action="{{ route('admin.media-library.store-video') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input class="upload-dropzone" type="file"
                   name="videos[]"
                   accept="video/mp4,video/quicktime,video/webm,video/avi,video/x-matroska"
                   multiple required>
            <button type="submit" class="btn-primary">Upload Videos</button>
        </form>
    </div>

    @if(count($videos) === 0)
        <div class="empty-state">
            No videos uploaded yet.<br>
            <small>Upload a video above, then copy its URL to use in a restaurant.</small>
        </div>
    @else
        <h3 style="font-size:16px; font-weight:600; margin-bottom:14px; color:#1a202c;">
            Library — {{ count($videos) }} video(s)
        </h3>
        <div class="media-grid">
            @foreach ($videos as $item)
                <div class="media-row">
                    <div>
                        {{-- Small inline preview --}}
                        <video class="media-thumb"
                               src="{{ $item['url'] }}"
                               muted preload="metadata"
                               style="object-fit: cover; cursor: pointer;"
                               title="Click to preview"
                               onclick="this.paused ? this.play() : this.pause()">
                        </video>
                        <div style="font-size:11px; color:#718096; margin-top:4px; text-align:center;">
                            <span class="badge-ext">{{ $item['ext'] }}</span>
                            {{ $item['size_mb'] }} MB
                        </div>
                    </div>
                    <div class="media-fields">
                        <div class="field-row">
                            <label>Public URL — paste into <em>video</em> column in Excel or restaurant Video URL field</label>
                            <input type="text" readonly value="{{ $item['url'] }}" id="vid-{{ $loop->index }}">
                            <div class="copy-actions">
                                <button type="button" class="btn-copy" data-copy-target="vid-{{ $loop->index }}">
                                    Copy URL
                                </button>
                                <a href="{{ $item['url'] }}" target="_blank"
                                   style="padding:7px 13px; font-size:12px; border-radius:6px;
                                          border:1px solid #e2e8f0; background:#fff; color:#4a5568;
                                          text-decoration:none; font-weight:500;">
                                    Open
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
// ── Tab switching ─────────────────────────────────────────────────────────
document.querySelectorAll('.ml-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
        var target = tab.getAttribute('data-tab');
        document.querySelectorAll('.ml-tab').forEach(function (t) { t.classList.remove('active'); });
        document.querySelectorAll('.ml-panel').forEach(function (p) { p.classList.remove('active'); });
        tab.classList.add('active');
        document.getElementById('tab-' + target).classList.add('active');
    });
});

// If a video was just uploaded, open the Videos tab automatically
@if(session('uploaded_video'))
    document.querySelectorAll('.ml-tab').forEach(function (t) { t.classList.remove('active'); });
    document.querySelectorAll('.ml-panel').forEach(function (p) { p.classList.remove('active'); });
    document.querySelector('[data-tab="videos"]').classList.add('active');
    document.getElementById('tab-videos').classList.add('active');
@endif

// ── Copy to clipboard ─────────────────────────────────────────────────────
document.querySelectorAll('.btn-copy').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var el = document.getElementById(btn.getAttribute('data-copy-target'));
        if (!el) return;
        el.select();
        el.setSelectionRange(0, 99999);
        var original = btn.textContent;
        navigator.clipboard.writeText(el.value)
            .then(function () {
                btn.textContent = 'Copied!';
                setTimeout(function () { btn.textContent = original; }, 1600);
            })
            .catch(function () {
                document.execCommand('copy');
                btn.textContent = 'Copied!';
                setTimeout(function () { btn.textContent = original; }, 1600);
            });
    });
});
</script>
@endpush
