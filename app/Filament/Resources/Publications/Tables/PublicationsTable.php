<?php

namespace App\Filament\Resources\Publications\Tables;

use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Publications\Actions\ModerationActions;
use App\Filament\Resources\Publications\PublicationResource;
use App\Models\Publication;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
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
                SelectFilter::make('status')->label('Statut')->options(PublicationStatus::class)->multiple()
                    ->default([PublicationStatus::Pending->value]),
                SelectFilter::make('publication_type_id')->label('Type')->relationship('type', 'name'),
                SelectFilter::make('category_id')->label('Catégorie')->relationship('category', 'name'),
                SelectFilter::make('nature')->label('Nature')->options(PublicationNature::class),
                SelectFilter::make('origin')->label('Origine')->options(PublicationOrigin::class),
                SelectFilter::make('territory')->label('Territoire')->relationship('commune.territory', 'name'),
                TrashedFilter::make(),
            ])
            ->recordUrl(fn (Publication $record) => PublicationResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('public')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Publication $record) => route('publications.show', $record->slug), shouldOpenInNewTab: true),
                ...ModerationActions::all(),
                EditAction::make(),
            ]);
    }
}
