<?php

namespace App\Services;

use App\Contracts\FileUploadServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class FileUploadService implements FileUploadServiceInterface
{
    /**
     * Image manager instance
     *
     * @var \Intervention\Image\ImageManager
     */
    protected $imageManager;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->imageManager = new ImageManager(new GdDriver());
    }

    /**
     * Handle file upload, replacement and deletion
     *
     * @param mixed $value Current value (UploadedFile, string, or null)
     * @param string $attributeName Name of attribute in the model
     * @param string $destinationPath Path to store the file (without disk name)
     * @param array $options Additional options
     * @param string|null $oldValue Previous value to be replaced
     * @return string|null Path where the file was stored or null if deleted/not uploaded
     */
    public function handleFileUpload($value, string $attributeName, string $destinationPath, array $options = [], ?string $oldValue = null)
    {
        // Set default options
        $defaultOptions = [
            'disk' => 'public',
            'createThumbnails' => false,
            'thumbnailWidth' => 200,
            'thumbnailHeight' => 200,
            'thumbnailPath' => 'thumbnails',
            'randomizeFilename' => true,
            'sanitizeFilename' => false, // New option for sanitizing filenames
            'allowedFileTypes' => [], // If empty, all types are allowed
            'maxFileSize' => 10240, // Default max file size in KB (10MB)
        ];

        // Merge options
        $options = array_merge($defaultOptions, $options);
        $disk = $options['disk'];

        // If a new file has been uploaded
        if ($value instanceof UploadedFile) {
            // Check file type if restrictions are in place
            if (!empty($options['allowedFileTypes'])) {
                $extension = strtolower($value->getClientOriginalExtension());
                $mimeType = $value->getMimeType();
                
                $allowed = false;
                foreach ($options['allowedFileTypes'] as $allowedType) {
                    // Check if extension matches or mime type starts with allowed type
                    if (strtolower($extension) === strtolower($allowedType) || 
                        strpos($mimeType, $allowedType) === 0) {
                        $allowed = true;
                        break;
                    }
                }
                
                if (!$allowed) {
                    throw new \Exception("File type not allowed. Allowed types: " . implode(', ', $options['allowedFileTypes']));
                }
            }

            // Check file size
            $fileSizeKB = $value->getSize() / 20480;
            if ($fileSizeKB > $options['maxFileSize']) {
                throw new \Exception("File size exceeds the maximum allowed size of {$options['maxFileSize']} KB");
            }

            // Delete old file if exists
            if (!empty($oldValue)) {
                $this->deleteFile($oldValue, $disk);
                
                // Delete thumbnails if they exist
                $this->deleteAssociatedFiles($oldValue, $options, $disk);
            }

            // Generate a filename
            $extension = strtolower($value->getClientOriginalExtension());
            
            if ($options['sanitizeFilename']) {
                // Get original filename and sanitize it
                $originalName = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);
                $filename = $this->generateUniqueFilename($originalName, $extension, $destinationPath, $disk);
            } else if ($options['randomizeFilename']) {
                $filename = md5($value->getClientOriginalName() . time()) . '.' . $extension;
            } else {
                $filename = Str::slug(pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $extension;
            }

            // Store the file
            $filePath = $value->storeAs($destinationPath, $filename, $disk);
            
            // For images, create thumbnails if option is enabled
            if ($this->isImage($value->getMimeType()) && $options['createThumbnails']) {
                $this->createThumbnail($filePath, $options, $disk);
            }
            
            return $filePath;
        }
        
        // If the file has been removed (null value and remove checkbox checked)
        if ($value === null && request()->has($attributeName . '_remove')) {
            // Delete the file if it exists
            if (!empty($oldValue)) {
                $this->deleteFile($oldValue, $disk);
                
                // Delete thumbnails if they exist
                $this->deleteAssociatedFiles($oldValue, $options, $disk);
            }
            
            return null;
        }
        
        // If no changes to the file, return the old value
        return $oldValue;
    }
    
    /**
     * Create thumbnail for an image
     *
     * @param string $filePath
     * @param array $options
     * @param string $disk
     * @return string|null
     */
    protected function createThumbnail(string $filePath, array $options, string $disk): ?string
    {
        try {
            $fileInfo = pathinfo($filePath);
            $thumbnailPath = $options['thumbnailPath'] . '/' . $fileInfo['dirname'] . '/' . $fileInfo['basename'];
            
            // Make the thumbnail path clean (remove any double slashes)
            $thumbnailPath = preg_replace('#/+#', '/', $thumbnailPath);
            
            Log::info('Creating thumbnail: ' . $thumbnailPath);
            
            // Make sure the directory exists
            $thumbnailDir = dirname(Storage::disk($disk)->path($thumbnailPath));
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            
            // Create the thumbnail
            $originalFullPath = Storage::disk($disk)->path($filePath);
            $thumbnailFullPath = Storage::disk($disk)->path($thumbnailPath);
            
            // Use Intervention Image to create thumbnail
            $image = $this->imageManager->read($originalFullPath);
            
            // Resize with aspect ratio constraint
            $image->resize($options['thumbnailWidth'], $options['thumbnailHeight']);
            
            // Set quality to 85% for better compression
            $image->save($thumbnailFullPath, 85);
            
            return $thumbnailPath;
        } catch (\Exception $e) {
            Log::error('Error creating thumbnail: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return null;
        }
    }
    
    /**
     * Delete associated files like thumbnails
     *
     * @param string $originalPath
     * @param array $options
     * @param string $disk
     * @return void
     */
    protected function deleteAssociatedFiles(string $originalPath, array $options, string $disk): void
    {
        // Delete thumbnail for images
        if ($this->isImage(Storage::disk($disk)->mimeType($originalPath) ?? '')) {
            $fileInfo = pathinfo($originalPath);
            $thumbnailPath = $options['thumbnailPath'] . '/' . $fileInfo['dirname'] . '/' . $fileInfo['basename'];
            $thumbnailPath = preg_replace('#/+#', '/', $thumbnailPath);
            
            Log::info('Deleting image thumbnail: ' . $thumbnailPath);
            $this->deleteFile($thumbnailPath, $disk);
        }
    }
    
    /**
     * Check if a file is an image based on mime type
     *
     * @param string $mimeType
     * @return bool
     */
    protected function isImage(string $mimeType): bool
    {
        return strpos($mimeType, 'image/') === 0;
    }
    
    /**
     * Delete a file from storage
     *
     * @param string|null $path
     * @param string $disk
     * @return bool
     */
    public function deleteFile(?string $path, string $disk): bool
    {
        if (empty($path)) {
            return false;
        }
        
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }
        
        return false;
    }
    
    /**
     * Get URL for a file
     *
     * @param string|null $path
     * @param string $disk
     * @return string|null
     */
    public function getFileUrl(?string $path, string $disk = 'public'): ?string
    {
        if (empty($path)) {
            return null;
        }
        
        return Storage::disk($disk)->url($path);
    }
    
    /**
     * Get URL for a thumbnail
     *
     * @param string|null $path
     * @param string $disk
     * @param string $thumbnailPath
     * @return string|null
     */
    public function getThumbnailUrl(?string $path, string $disk = 'public', string $thumbnailPath = 'thumbnails'): ?string
    {
        if (empty($path)) {
            return null;
        }
        
        // Only for images
        if ($this->isImage(Storage::disk($disk)->mimeType($path) ?? '')) {
            $fileInfo = pathinfo($path);
            $thumbnailFullPath = $thumbnailPath . '/' . $fileInfo['dirname'] . '/' . $fileInfo['basename'];
            $thumbnailFullPath = preg_replace('#/+#', '/', $thumbnailFullPath);
            
            Log::info('Looking for image thumbnail at: ' . $thumbnailFullPath);
            
            if (Storage::disk($disk)->exists($thumbnailFullPath)) {
                return Storage::disk($disk)->url($thumbnailFullPath);
            }
        }
        
        // If no thumbnail, return the original file
        return $this->getFileUrl($path, $disk);
    }
    
    /**
     * Get file type icon
     *
     * @param string $filePath
     * @return string
     */
    public function getFileTypeIcon(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $iconMap = [
            // Documents
            'pdf' => 'la-file-pdf',
            'doc' => 'la-file-word',
            'docx' => 'la-file-word',
            'odt' => 'la-file-word',
            'rtf' => 'la-file-word',
            'txt' => 'la-file-alt',
            'md' => 'la-file-alt',
            
            // Spreadsheets
            'xls' => 'la-file-excel',
            'xlsx' => 'la-file-excel',
            'ods' => 'la-file-excel',
            'csv' => 'la-file-csv',
            
            // Presentations
            'ppt' => 'la-file-powerpoint',
            'pptx' => 'la-file-powerpoint',
            'odp' => 'la-file-powerpoint',
            
            // Images
            'jpg' => 'la-file-image',
            'jpeg' => 'la-file-image',
            'png' => 'la-file-image',
            'gif' => 'la-file-image',
            'svg' => 'la-file-image',
            'webp' => 'la-file-image',
            'bmp' => 'la-file-image',
            'tiff' => 'la-file-image',
            
            // Archives
            'zip' => 'la-file-archive',
            'rar' => 'la-file-archive',
            'tar' => 'la-file-archive',
            'gz' => 'la-file-archive',
            '7z' => 'la-file-archive',
            
            // Audio
            'mp3' => 'la-file-audio',
            'wav' => 'la-file-audio',
            'ogg' => 'la-file-audio',
            
            // Video
            'mp4' => 'la-file-video',
            'avi' => 'la-file-video',
            'mov' => 'la-file-video',
            'wmv' => 'la-file-video',
            'mkv' => 'la-file-video',
            
            // Code
            'html' => 'la-file-code',
            'css' => 'la-file-code',
            'js' => 'la-file-code',
            'php' => 'la-file-code',
            'py' => 'la-file-code',
            'json' => 'la-file-code',
            'xml' => 'la-file-code',
        ];
        
        return $iconMap[$extension] ?? 'la-file';
    }
    
    /**
     * Sanitize filename - remove diacritics, special characters and replace spaces with underscores
     *
     * @param string $filename
     * @return string
     */
    public function sanitizeFilename(string $filename): string
    {
        // Transliterate (convert accented characters to ASCII)
        $filename = transliterator_transliterate('Any-Latin; Latin-ASCII', $filename);
        
        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);
        
        // Remove any non-alphanumeric characters except for underscores and dashes
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $filename);
        
        // Ensure the filename isn't too long (max 100 chars)
        $filename = substr($filename, 0, 100);
        
        // Make sure the filename is not empty
        if (empty($filename) || $filename === '.') {
            $filename = 'file';
        }
        
        return $filename;
    }

    /**
     * Generate a unique filename based on original name, avoiding duplicates
     *
     * @param string $originalName Base filename (without extension)
     * @param string $extension File extension
     * @param string $destinationPath Path where file will be stored
     * @param string $disk Storage disk
     * @return string
     */
    public function generateUniqueFilename(string $originalName, string $extension, string $destinationPath, string $disk = 'public'): string
    {
        // Sanitize the base name
        $baseName = $this->sanitizeFilename($originalName);
        
        // Add extension
        $filename = $baseName . '.' . $extension;
        
        // Check for duplicates
        $counter = 1;
        while (Storage::disk($disk)->exists($destinationPath . '/' . $filename)) {
            $filename = $baseName . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        return $filename;
    }
    
    /**
     * Get URL for a file or specific file from an array of files
     *
     * @param mixed $value File path or array of file paths
     * @param int|null $index Index if $value is an array
     * @param string $disk Storage disk
     * @return string|null
     */
    public function getFileUrlFromAttribute($value, ?int $index = null, string $disk = 'public'): ?string
    {
        // Handle array of files with index
        if (is_array($value) && isset($index)) {
            return isset($value[$index]) ? $this->getFileUrl($value[$index], $disk) : null;
        }
        
        // Handle string value (single file)
        if (is_string($value)) {
            return $this->getFileUrl($value, $disk);
        }
        
        // Handle JSON encoded value
        if (is_string($value) && json_validate($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && isset($index) && isset($decoded[$index])) {
                return $this->getFileUrl($decoded[$index], $disk);
            }
        }
        
        return null;
    }
}
