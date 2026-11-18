<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'email_verified_at', 'password', 'phone', 'website', 'show_email', 'show_phone', 'show_website', 'is_trusted'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'show_website' => 'boolean',
            'anonymized_at' => 'datetime',
            'is_trusted' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasVerifiedEmail() && $this->can(Permission::AccessAdmin->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin->value);
    }

    /**
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Publication, $this>
     */
    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    /**
     * Publie sans validation préalable : utilisateur de confiance ou modérateur.
     */
    public function canPublishWithoutModeration(): bool
    {
        return $this->is_trusted || $this->can(Permission::ModeratePublications->value);
    }

    /**
     * Comptes actifs disposant d'une permission (destinataires des alertes d'administration).
     *
     * @param  Builder<self>  $query
     */
    public function scopeActiveWithPermission(Builder $query, Permission $permission): void
    {
        $query->permission($permission->value)
            ->whereNull('anonymized_at')
            ->whereNotNull('email_verified_at');
    }

    /**
     * @return HasMany<ContactMessage, $this>
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'recipient_id');
    }

    /**
     * @return HasMany<VisibilityPurchase, $this>
     */
    public function visibilityPurchases(): HasMany
    {
        return $this->hasMany(VisibilityPurchase::class);
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }

    public function publicEmail(): ?string
    {
        return $this->show_email ? $this->email : null;
    }

    public function publicPhone(): ?string
    {
        return $this->show_phone ? $this->phone : null;
    }

    public function publicWebsite(): ?string
    {
        return $this->show_website ? $this->website : null;
    }
}
