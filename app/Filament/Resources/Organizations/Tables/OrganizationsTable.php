<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('commune.territory')->withCount('members'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('logo_path')->label('')->disk('public')->circular()->imageSize(36),
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('type')->label('Type')->badge()->sortable(),
                TextColumn::make('commune.name')->label('Commune')->searchable()->sortable(),
                TextColumn::make('commune.territory.name')->label('Territoire')->toggleable(),
                TextColumn::make('members_count')->label('Responsables')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('created_at')->label('Créée le')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Type')->options(OrganizationType::class),
                SelectFilter::make('territory')
                    ->label('Territoire')
                    ->relationship('commune.territory', 'name'),
                TernaryFilter::make('is_active')->label('Active'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('public')
                    ->label('Fiche')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Organization $record) => route('organizations.show', $record), shouldOpenInNewTab: true)
                    ->hidden(fn (Organization $record) => $record->trashed()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
