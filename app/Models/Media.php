<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'user_id',
        'name',
        'file_name',
        'mime_type',
        'path',
        'disk',
        'file_hash',
        'size',
        'custom_properties',
    ];

    protected $casts = [
        'custom_properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['url'];

    protected $with = ['user'];

    public $timestamps = true;

    protected static function imageManager()
    {
        return new ImageManager(new Driver());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'mediable');
    }

    public function getUrlAttribute(): string
    {
        return $this->path ? Storage::disk($this->disk)->url($this->path) : '';
    }

    public function getDimensionsAttribute(): ?array
    {
        if (!Str::startsWith($this->mime_type, 'image/')) {
            return null;
        }

        $image = self::imageManager()->read(Storage::disk($this->disk)->path($this->path));
        return [
            'width' => $image->width(),
            'height' => $image->height(),
        ];
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        $size = round($bytes / pow(1024, $i), 2);

        return $size . ' ' . $units[$i];
    }

    public function delete()
    {
        Storage::disk($this->disk)->delete($this->path);
        return parent::delete();
    }

    public static function upload($file, $directory = 'uploads', $disk = 'public'): self
    {
        try {
            $hash = md5_file($file->path());
            $extension = $file->getClientOriginalExtension();
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType() ?? $file->getClientMimeType();
            $size = $file->getSize();

            // Check if file with same hash exists
            $existingMedia = static::where('file_hash', $hash)->first();
            if ($existingMedia) {
                // Generate new unique hash by combining original hash with timestamp
                $hash = md5($hash . time());
            }

            // Generate unique filename
            $baseFileName = pathinfo($originalName, PATHINFO_FILENAME);
            $counter = 1;
            do {
                $fileName = Str::random(40) . '.' . $extension;
                $path = $directory . '/' . $fileName;
            } while (Storage::disk($disk)->exists($path));

            // Handle file upload based on type
            if (Str::startsWith($mimeType, 'image/')) {
                // Handle image files with Intervention Image
                $image = self::imageManager()->read($file);
                Storage::disk($disk)->put($path, $image->encode());
            } else {
                // Handle other file types directly
                Storage::disk($disk)->put($path, file_get_contents($file->path()));
            }

            // Verify file was uploaded
            if (!Storage::disk($disk)->exists($path)) {
                throw new \Exception('Failed to upload file');
            }

            // Get actual file size after upload
            $actualSize = Storage::disk($disk)->size($path);

            // If original name already exists, add counter
            $finalName = $originalName;
            if (static::where('name', $finalName)->exists()) {
                $pathInfo = pathinfo($originalName);
                $counter = 1;
                do {
                    $finalName = $pathInfo['filename'] . ' (' . $counter . ').' . $pathInfo['extension'];
                    $counter++;
                } while (static::where('name', $finalName)->exists());
            }

            $media = static::create([
                'user_id' => Auth::id() ?? 1,
                'name' => $finalName,
                'file_name' => $fileName,
                'mime_type' => $mimeType,
                'path' => $path,
                'disk' => $disk,
                'file_hash' => $hash,
                'size' => $actualSize > 0 ? $actualSize : $size,
                'custom_properties' => [],
            ]);

            // Log creation
            \Log::info('Media model created:', $media->toArray());

            // Ensure user relation is loaded
            return $media->load('user');
        } catch (\Exception $e) {
            \Log::error('Failed to upload media:', [
                'error' => $e->getMessage(),
                'file' => $originalName ?? 'unknown',
            ]);
            throw $e;
        }
    }

    public function optimize()
    {
        if (!Str::startsWith($this->mime_type, 'image/')) {
            return $this;
        }

        $path = Storage::disk($this->disk)->path($this->path);
        
        $image = self::imageManager()->read($path);

        // Resize if width is greater than 2000px
        if ($image->width() > 2000) {
            $image->scale(width: 2000);
        }

        // Optimize quality
        $image->save($path, 80);

        // Update size
        $this->update([
            'size' => filesize($path),
        ]);

        return $this;
    }
}
