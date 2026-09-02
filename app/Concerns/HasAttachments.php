<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Adds attachment capability to a model via Spatie Media Library.
 */
trait HasAttachments
{
    use InteractsWithMedia;

    /**
     * Get the attachments collection name.
     */
    public function getAttachmentsCollectionName(): string
    {
        return 'attachments';
    }

    /**
     * Register the attachments media collection.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection($this->getAttachmentsCollectionName())
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/msword',
                'application/vnd.ms-excel',
                'text/csv',
                'application/zip',
                'application/vnd.autoCAD',
                'application/dxf',
            ]);
    }

    /**
     * Add an attachment to the model.
     *
     * @param  array<string, mixed>  $properties
     */
    public function addAttachment(UploadedFile $file, ?string $name = null, array $properties = []): Media
    {
        $media = $this->addMedia($file)
            ->withCustomProperties($properties)
            ->toMediaCollection($this->getAttachmentsCollectionName());

        if ($name) {
            $media->update(['name' => $name]);
        }

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->event('attachment_added')
            ->withProperties(['file_name' => $media->file_name, 'name' => $name ?? $media->file_name])
            ->log('Attachment uploaded');

        return $media;
    }

    /**
     * Get all attachments.
     *
     * @return Collection<int, Media>
     */
    public function getAttachments()
    {
        return $this->getMedia($this->getAttachmentsCollectionName());
    }

    /**
     * Get the count of attachments.
     */
    public function getAttachmentCount(): int
    {
        return $this->getMedia($this->getAttachmentsCollectionName())->count();
    }

    /**
     * Remove an attachment by ID.
     */
    public function removeAttachment(int $mediaId): bool
    {
        $media = $this->getMedia($this->getAttachmentsCollectionName())
            ->firstWhere('id', $mediaId);

        if ($media) {
            activity()
                ->performedOn($this)
                ->causedBy(auth()->user())
                ->event('attachment_removed')
                ->withProperties(['file_name' => $media->file_name])
                ->log('Attachment removed');

            return $media->delete();
        }

        return false;
    }
}
