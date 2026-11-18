<?php

namespace App\Filament\Resources\Publications\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'reports';

    protected static ?string $title = 'Signalements';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
                TextColumn::make('reason')->label('Motif')->badge()->color('gray'),
                TextColumn::make('comment')->label('Commentaire')->wrap()->limit(120)->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge(),
            ]);
    }
}
