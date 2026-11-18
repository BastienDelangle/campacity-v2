<?php

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Actions\Organizations\ManageOrganizationMembers;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

/**
 * Responsables d'une organisation. Les retraits et changements de rôle
 * passent par ManageOrganizationMembers pour garantir qu'il reste un propriétaire.
 */
class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Responsables';

    protected static ?string $modelLabel = 'responsable';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('pivot.role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => OrganizationRole::from($state)->label()),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Ajouter un responsable')
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->recordSelectOptionsQuery(fn ($query) => $query->whereNull('anonymized_at'))
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action) => [
                        $action->getRecordSelect()->label('Utilisateur'),
                        Select::make('role')->label('Rôle')->options(OrganizationRole::class)->default(OrganizationRole::Editor->value)->required(),
                    ]),
            ])
            ->recordActions([
                Action::make('changeRole')
                    ->label('Rôle')
                    ->icon('heroicon-o-arrows-right-left')
                    ->schema([
                        Select::make('role')->label('Rôle')->options(OrganizationRole::class)->required(),
                    ])
                    ->fillForm(fn (User $record) => ['role' => $record->pivot->role])
                    ->action(fn (User $record, array $data) => $this->guard(fn (ManageOrganizationMembers $members) => $members->changeRole($this->organization(), $record, OrganizationRole::from($data['role'] instanceof OrganizationRole ? $data['role']->value : $data['role'])))),
                Action::make('remove')
                    ->label('Retirer')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $this->guard(fn (ManageOrganizationMembers $members) => $members->remove($this->organization(), $record))),
            ]);
    }

    private function organization(): Organization
    {
        /** @var Organization */
        return $this->getOwnerRecord();
    }

    /**
     * Exécute une règle métier et transforme ses erreurs en notification.
     */
    private function guard(callable $callback): void
    {
        try {
            app()->call($callback);
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
        }
    }
}
