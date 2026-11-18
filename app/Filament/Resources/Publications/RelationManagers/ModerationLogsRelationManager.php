<?php

namespace App\Filament\Resources\Publications\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModerationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'moderationLogs';

    protected static ?string $title = 'Historique de modération';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
                TextColumn::make('action')->label('Action')->badge(),
                TextColumn::make('user.name')->label('Par')->placeholder('Automatique'),
                TextColumn::make('reason')->label('Motif')->wrap()->placeholder('—'),
            ])
            ->paginated([10, 25]);
    }
}
