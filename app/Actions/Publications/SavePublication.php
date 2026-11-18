<?php

namespace App\Actions\Publications;

use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\User;
use App\Services\ImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Création et modification du contenu d'une publication (champs, événement,
 * images, pièces jointes). Les changements de statut relèvent de PublicationLifecycle.
 */
class SavePublication
{
    public function __construct(
        private readonly ImageStorage $images,
        private readonly PublicationLifecycle $lifecycle,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Données validées (PublicationRequest).
     */
    public function create(User $author, array $data): Publication
    {
        return DB::transaction(function () use ($author, $data) {
            $publication = new Publication($this->attributes($data));
            $publication->user_id = $author->id;
            $publication->save();

            $this->syncEvent($publication, $data);
            $this->addImages($publication, $data['images'] ?? []);
            $this->addAttachments($publication, $data['attachments'] ?? []);

            return $publication;
        });
    }

    /**
     * @param  array<string, mixed>  $data  Données validées (PublicationRequest).
     */
    public function update(Publication $publication, User $actor, array $data): Publication
    {
        DB::transaction(function () use ($publication, $actor, $data) {
            $publication->fill($this->attributes($data));
            $changed = array_keys($publication->getDirty());
            $publication->save();

            $this->syncEvent($publication, $data);
            $this->removeImages($publication, $data['remove_images'] ?? []);
            $this->updateImageAlts($publication, $data['image_alts'] ?? []);

            if (! empty($data['images'])) {
                $this->addImages($publication, $data['images']);
                $changed[] = 'images';
            }

            $this->removeAttachments($publication, $data['remove_attachments'] ?? []);
            $this->addAttachments($publication, $data['attachments'] ?? []);

            $this->lifecycle->afterEdit($publication, $actor, $changed);
        });

        return $publication;
    }

    /**
     * Suppression définitive des fichiers (appelée lors d'une suppression physique).
     */
    public function deleteFiles(Publication $publication): void
    {
        Storage::disk('public')->deleteDirectory("publications/{$publication->id}");
        Storage::disk('local')->deleteDirectory("publications/{$publication->id}");
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return collect($data)->only([
            'organization_id', 'publication_type_id', 'category_id', 'nature', 'origin',
            'title', 'description', 'commune_id', 'address', 'latitude', 'longitude',
            'location_precision', 'contact_form_enabled', 'meta_title', 'meta_description',
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncEvent(Publication $publication, array $data): void
    {
        $type = PublicationType::find($publication->publication_type_id);

        if (! $type?->is_event) {
            $publication->event()->delete();

            return;
        }

        $event = $data['event'] ?? [];
        $date = fn (?string $day, ?string $time) => $day ? $day.' '.($time ?: '00:00') : null;

        $publication->event()->updateOrCreate([], [
            'starts_at' => $date($event['start_date'] ?? null, $event['start_time'] ?? null),
            'ends_at' => $date($event['end_date'] ?? null, $event['end_time'] ?? null)
                ?? (! empty($event['end_time']) ? $date($event['start_date'], $event['end_time']) : null),
            'venue' => $event['venue'] ?? null,
            'organizer' => $event['organizer'] ?? null,
            'program' => $event['program'] ?? null,
            'registration_url' => $event['registration_url'] ?? null,
        ]);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function addImages(Publication $publication, array $files): void
    {
        $order = (int) $publication->images()->max('sort_order');

        foreach ($files as $file) {
            $stored = $this->images->store($file, "publications/{$publication->id}");

            $publication->images()->create([
                'path' => $stored['path'],
                'width' => $stored['width'],
                'height' => $stored['height'],
                'sort_order' => ++$order,
            ]);
        }
    }

    /**
     * @param  list<int|string>  $ids
     */
    private function removeImages(Publication $publication, array $ids): void
    {
        $publication->images()->whereKey($ids)->get()->each(function ($image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        });
    }

    /**
     * @param  array<int|string, ?string>  $alts
     */
    private function updateImageAlts(Publication $publication, array $alts): void
    {
        foreach ($publication->images()->whereKey(array_keys($alts))->get() as $image) {
            $image->update(['alt' => $alts[$image->id] ?: null]);
        }
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function addAttachments(Publication $publication, array $files): void
    {
        foreach ($files as $file) {
            $publication->attachments()->create([
                'path' => $file->store("publications/{$publication->id}/attachments", 'local'),
                'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }
    }

    /**
     * @param  list<int|string>  $ids
     */
    private function removeAttachments(Publication $publication, array $ids): void
    {
        $publication->attachments()->whereKey($ids)->get()->each(function ($attachment) {
            Storage::disk('local')->delete($attachment->path);
            $attachment->delete();
        });
    }
}
