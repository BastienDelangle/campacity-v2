<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Génère un slug unique à la création. Le slug n'est pas modifié lors d'un
 * renommage, pour préserver les URLs publiques déjà indexées.
 *
 * @mixin Model
 */
trait HasUniqueSlug
{
    public static function bootHasUniqueSlug(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug($model->slugSource());
            }
        });
    }

    protected function slugSource(): string
    {
        return (string) $this->name;
    }

    public function generateUniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: Str::lower(Str::random(8));
        $base = Str::limit($base, 180, '');
        $slug = $base;
        $suffix = 2;

        $query = fn (string $candidate) => static::query()
            ->when(in_array(SoftDeletes::class, class_uses_recursive(static::class), true), fn ($q) => $q->withTrashed())
            ->where('slug', $candidate)
            ->exists();

        while ($query($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
