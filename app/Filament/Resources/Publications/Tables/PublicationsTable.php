<?php

namespace App\Filament\Resources\Publications\Tables;

use App\Actions\Publications\PublicationLifecycle;
use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use App\Enums\PublicationStatus;
use App\Exceptions\InvalidPublicationTransition;
use App\Models\Publication;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PublicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['type', 'category', 'author', 'organization', 'commune']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable()->limit(50)->tooltip(fn (Publication $record) => $record->title),
                TextColumn::make('status')->label('Statut')->badge()->sortable(),
                TextColumn::make('type.name')->label('Type')->toggleable(),
                TextColumn::make('category.name')->label('Catégorie')->toggleable(),
                TextColumn::make('nature')->label('Nature')->badge()->color('gray')->toggleable(),
                TextColumn::make('origin')->label('Origine')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('author.name')->label('Auteur')->searchable()->description(fn (Publication $record) => $record->organization?->name),
                TextColumn::make('commune.name')->label('Commune')->toggleable(),
                TextColumn::make('submitted_at')->label('Soumise le')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
                TextColumn::make('expires_at')->label('Expire le')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(PublicationStatus::class)->multiple(),
                SelectFilter::make('publication_type_id')->label('Type')->relationship('type', 'name'),
                SelectFilter::make('category_id')->label('Catégorie')->relationship('category', 'name'),
                SelectFilter::make('nature')->label('Nature')->options(PublicationNature::class),
                SelectFilter::make('origin')->label('Origine')->options(PublicationOrigin::class),
                SelectFilter::make('territory')->label('Territoire')->relationship('commune.territory', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Publication $record) => route('publications.show', $record->slug), shouldOpenInNewTab: true),
                Action::make('approve')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Publication $record) => $record->status === PublicationStatus::Pending && auth()->user()->can('moderate', $record))
                    ->action(fn (Publication $record, PublicationLifecycle $lifecycle) => self::transition(fn () => $lifecycle->publish($record), 'Publication validée et mise en ligne')),
                Action::make('archive')
                    ->label('Archiver')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Publication $record) => ! in_array($record->status, [PublicationStatus::Archived, PublicationStatus::Draft], true) && auth()->user()->can('moderate', $record))
                    ->action(fn (Publication $record, PublicationLifecycle $lifecycle) => self::transition(fn () => $lifecycle->archive($record), 'Publication archivée')),
                EditAction::make(),
            ]);
    }

    private static function transition(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->success()->title($success)->send();
        } catch (InvalidPublicationTransition $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();
        }
    }
}
