<?php

namespace App\Traits;

use App\Contracts\FileUploadServiceInterface;

trait HasFileUploads
{
    /**
     * Handle file attribute upload
     *
     * @param string $attribute
     * @param mixed $value
     * @param string $path
     * @param array $options
     * @return void
     */
    protected function handleFileUpload(string $attribute, $value, string $path, array $options = []): void
    {
        $fileService = app(FileUploadServiceInterface::class);
        $oldValue = $this->attributes[$attribute] ?? null;
        
        $this->attributes[$attribute] = $fileService->handleFileUpload(
            $value,
            $attribute,
            $path,
            $options,
            $oldValue
        );
    }
    
    /**
     * Get URL for a file attribute
     *
     * @param string $attribute
     * @param string $disk
     * @return string|null
     */
    protected function getFileUrl(string $attribute, string $disk = 'public'): ?string
    {
        $fileService = app(FileUploadServiceInterface::class);
        return $fileService->getFileUrl($this->{$attribute}, $disk);
    }
    
    /**
     * Get URL for a thumbnail or preview of a file attribute
     *
     * @param string $attribute
     * @param string $thumbnailFolder
     * @param string $disk
     * @return string|null
     */
    protected function getThumbnailUrl(string $attribute, string $thumbnailFolder = 'thumbnails', string $disk = 'public'): ?string
    {
        $fileService = app(FileUploadServiceInterface::class);
        // Updated to use the new interface signature
        return $fileService->getThumbnailUrl($this->{$attribute}, $disk, $thumbnailFolder);
    }
    
    /**
     * Determine if file is an image
     *
     * @param string $attribute
     * @return bool
     */
    protected function isFileImage(string $attribute): bool
    {
        if (empty($this->{$attribute})) {
            return false;
        }
        
        $extension = pathinfo($this->{$attribute}, PATHINFO_EXTENSION);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
        
        return in_array(strtolower($extension), $imageExtensions);
    }
    
    /**
     * Get URL for file attribute or specific file from an array attribute
     *
     * @param string $attribute Name of the attribute
     * @param int|null $index Index if the attribute is an array
     * @param string $disk Storage disk
     * @return string|null
     */
    public function getAttributeFileUrl(string $attribute, ?int $index = null, string $disk = 'public'): ?string
    {
        $fileService = app(FileUploadServiceInterface::class);
        $value = $this->{$attribute};
        
        return $fileService->getFileUrlFromAttribute($value, $index ?? 0, $disk);
    }
}
