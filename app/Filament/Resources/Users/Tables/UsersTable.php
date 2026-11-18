<?php

namespace App\Filament\Resources\Users\Tables;

use App\Actions\Users\AnonymizeUser;
use App\Enums\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('roles')->withCount('organizations'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->copyable(),
                TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Role::tryFrom($state)?->label() ?? $state),
                IconColumn::make('is_trusted')->label('Confiance')->boolean(),
                TextColumn::make('organizations_count')->label('Organisations')->sortable(),
                IconColumn::make('email_verified_at')->label('Vérifié')->boolean()->getStateUsing(fn (User $record) => $record->email_verified_at !== null),
                TextColumn::make('anonymized_at')->label('Anonymisé le')->dateTime('d/m/Y')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Inscrit le')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Rôle')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Model $record) => Role::tryFrom($record->name)?->label() ?? $record->name),
                TernaryFilter::make('is_trusted')->label('Utilisateur de confiance'),
                TernaryFilter::make('email_verified_at')->label('E-mail vérifié')->nullable(),
                TernaryFilter::make('anonymized')
                    ->label('Comptes anonymisés')
                    ->placeholder('Masqués')
                    ->trueLabel('Uniquement')
                    ->falseLabel('Inclus')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('anonymized_at'),
                        false: fn ($query) => $query,
                        blank: fn ($query) => $query->whereNull('anonymized_at'),
                    ),
            ])
            ->recordActions([
                EditAction::make()->hidden(fn (User $record) => $record->isAnonymized()),
                Action::make('anonymize')
                    ->label('Anonymiser')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Les données personnelles seront définitivement effacées. Le compte ne pourra plus se connecter.')
                    ->visible(fn (User $record) => ! $record->isAnonymized() && auth()->user()->can('delete', $record) && ! auth()->user()->is($record))
                    ->action(function (User $record, AnonymizeUser $anonymize): void {
                        try {
                            $anonymize->handle($record);
                            Notification::make()->success()->title('Compte anonymisé')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
                        }
                    }),
            ]);
    }
}
