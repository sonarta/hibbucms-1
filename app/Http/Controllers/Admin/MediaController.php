<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view media')->only(['index', 'show']);
        $this->middleware('permission:create media')->only(['create', 'store']);
        $this->middleware('permission:edit media')->only(['edit', 'update']);
        $this->middleware('permission:delete media')->only('destroy');
    }

    public function index(Request $request)
    {
        $folderId = $request->query('folder_id');
        $currentFolder = $folderId ? MediaFolder::with('parent')->findOrFail($folderId) : null;

        // Get folders
        $folders = MediaFolder::where('parent_id', $folderId)
            ->orderBy('name')
            ->get()
            ->map(function ($folder) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'is_folder' => true,
                    'items_count' => $folder->media()->count() + $folder->children()->count(),
                ];
            });

        $query = Media::with('user')->latest();

        // Filter by folder
        $query->where('folder_id', $folderId);

        // Filter by type
        if ($request->has('type')) {
            $query->where('mime_type', 'like', $request->type . '/%');
        }

        // Filter by search term
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $media = $query->paginate(24)->through(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'url' => $item->url,
                'mime_type' => $item->mime_type,
                'size' => $item->human_readable_size,
                'dimensions' => $item->dimensions,
                'created_at' => $item->created_at,
                'is_folder' => false,
                'user' => $item->user ? [
                    'name' => $item->user->name,
                ] : null,
            ];
        });

        // Build breadcrumbs
        $breadcrumbs = [];
        if ($currentFolder) {
            $temp = $currentFolder;
            while ($temp) {
                array_unshift($breadcrumbs, [
                    'id' => $temp->id,
                    'name' => $temp->name,
                ]);
                $temp = $temp->parent;
            }
        }

        return Inertia::render('Admin/Media/Index', [
            'folders' => $folders,
            'allFolders' => MediaFolder::select('id', 'name', 'parent_id')->orderBy('name')->get(),
            'media' => $media,
            'currentFolder' => $currentFolder,
            'breadcrumbs' => $breadcrumbs,
            'filters' => $request->only(['search', 'type', 'folder_id']),
        ]);
    }

    public function storeFolder(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_folders,id',
        ]);

        MediaFolder::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
        ]);

        return back()->with('success', 'Folder created successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // Max 10MB per file
            'folder_id' => 'nullable|exists:media_folders,id',
        ]);

        $uploadedMedia = [];

        foreach ($request->file('files') as $file) {
            // Debug log before upload
            Log::info('Uploading file:', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            $media = Media::upload($file)
                ->optimize();

            // Update folder_id if present
            if ($request->folder_id) {
                $media->update(['folder_id' => $request->folder_id]);
            }

            // Debug log after upload
            Log::info('Media created:', [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'path' => $media->path,
                'url' => $media->url,
                'size' => $media->size,
                'user_id' => $media->user_id,
                'user' => $media->user,
                'folder_id' => $media->folder_id,
            ]);

            $uploadedMedia[] = [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->url,
                'mime_type' => $media->mime_type,
                'size' => $media->human_readable_size,
            ];
        }

        if ($request->wantsJson()) {
            return response()->json($uploadedMedia);
        }

        return back()->with('success', 'Media uploaded successfully.');
    }

    public function show($id)
    {
        $media = Media::with('user')->findOrFail($id);

        // Debug log
        Log::info('Media data:', [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'path' => $media->path,
            'url' => $media->url,
            'size' => $media->size,
            'human_readable_size' => $media->human_readable_size,
            'user_id' => $media->user_id,
            'user' => $media->user,
            'created_at' => $media->created_at
        ]);

        if (!$media->user) {
            $media->update(['user_id' => auth()->id() ?? 1]);
            $media->load('user');
        }

        return Inertia::render('Admin/Media/Show', [
            'media' => [
                'id' => $media->id,
                'name' => $media->name ?: $media->file_name,
                'url' => $media->url,
                'mime_type' => $media->mime_type,
                'size' => $media->human_readable_size,
                'dimensions' => $media->dimensions,
                'created_at' => $media->created_at ? $media->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                'user' => $media->user ? [
                    'name' => $media->user->name,
                ] : null,
            ],
        ]);
    }

    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        $media->delete();

        return redirect()->back()
            ->with('message', 'Media deleted successfully.');
    }

    public function destroyFolder($id) {
        $folder = MediaFolder::findOrFail($id);
        // Logic for deleting folder (recursive or move to parent)?
        // Current constraint is nullOnDelete, so children will move to root.
        // We might want to warn user. For now, simple delete.
        $folder->delete();

        return redirect()->back()
            ->with('message', 'Folder deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:media,id',
            'folder_ids' => 'array',
            'folder_ids.*' => 'exists:media_folders,id',
        ]);

        if ($request->has('ids')) {
            $media = Media::whereIn('id', $request->ids)->get();
            foreach ($media as $item) {
                $item->delete();
            }
        }

        if ($request->has('folder_ids')) {
            MediaFolder::destroy($request->folder_ids);
        }

        return back()->with('success', 'Selected items deleted successfully.');
    }

    public function move(Request $request)
    {
        $request->validate([
            'ids' => 'array',
            'ids.*' => 'exists:media,id',
            'folder_ids' => 'array',
            'folder_ids.*' => 'exists:media_folders,id',
            'target_folder_id' => 'nullable|exists:media_folders,id',
        ]);

        if ($request->has('ids')) {
             Media::whereIn('id', $request->ids)->update(['folder_id' => $request->target_folder_id]);
        }

        if ($request->has('folder_ids')) {
             // Prevent moving folder into itself or its children (simple check)
             // For strict check we need to check hierarchy.
             // For now, just update parent_id if it's not the same.
             foreach($request->folder_ids as $fid) {
                 if ($fid != $request->target_folder_id) {
                     MediaFolder::where('id', $fid)->update(['parent_id' => $request->target_folder_id]);
                 }
             }
        }

        return back()->with('success', 'Items moved successfully.');
    }

    public function download($id)
    {
        $media = Media::findOrFail($id);
        return Storage::disk($media->disk)->download(
            $media->path,
            $media->name
        );
    }
}
