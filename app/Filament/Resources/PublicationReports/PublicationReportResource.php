<?php

namespace App\Filament\Resources\PublicationReports;

use App\Actions\Publications\PublicationLifecycle;
use App\Enums\PublicationStatus;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Exceptions\InvalidPublicationTransition;
use App\Filament\Resources\PublicationReports\Pages\ManagePublicationReports;
use App\Filament\Resources\Publications\PublicationResource;
use App\Models\PublicationReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class PublicationReportResource extends Resource
{
    protected static ?string $model = PublicationReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'signalement';

    protected static ?string $pluralModelLabel = 'signalements';

    public static function getNavigationBadge(): ?string
    {
        $open = PublicationReport::where('status', ReportStatus::Open)->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['publication', 'reporter', 'handler']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('publication.title')->label('Publication')->limit(45)->searchable()
                    ->url(fn (PublicationReport $record) => PublicationResource::getUrl('view', ['record' => $record->publication])),
                TextColumn::make('publication.status')->label('Statut publication')->badge(),
                TextColumn::make('reason')->label('Motif')->badge()->color('gray'),
                TextColumn::make('comment')->label('Commentaire')->limit(80)->wrap()->placeholder('—'),
                TextColumn::make('reporter_email')->label('Signalé par')->placeholder('Anonyme')->toggleable(),
                TextColumn::make('status')->label('Traitement')->badge(),
                TextColumn::make('handler.name')->label('Traité par')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resolution_note')->label('Note')->limit(60)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Traitement')->options(ReportStatus::class)->default(ReportStatus::Open->value),
                SelectFilter::make('reason')->label('Motif')->options(ReportReason::class),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Traiter')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (PublicationReport $record) => $record->status === ReportStatus::Open)
                    ->schema([
                        Toggle::make('suspend')
                            ->label('Suspendre la publication')
                            ->live()
                            ->visible(fn (PublicationReport $record) => $record->publication && ! $record->publication->trashed()
                                && $record->publication->status !== PublicationStatus::Suspended),
                        Textarea::make('reason')
                            ->label('Motif de suspension (transmis à l\'auteur)')
                            ->visible(fn (Get $get) => (bool) $get('suspend'))
                            ->required(fn (Get $get) => (bool) $get('suspend'))
                            ->minLength(10)->maxLength(2000),
                        Textarea::make('resolution_note')->label('Note interne')->maxLength(2000),
                    ])
                    ->action(function (PublicationReport $record, array $data, PublicationLifecycle $lifecycle): void {
                        try {
                            DB::transaction(function () use ($record, $data, $lifecycle) {
                                if (! empty($data['suspend'])) {
                                    $lifecycle->suspend($record->publication, auth()->user(), $data['reason']);
                                }
                                self::close($record, ReportStatus::Resolved, $data['resolution_note'] ?? null);
                            });
                            Notification::make()->success()->title('Signalement traité')->send();
                        } catch (InvalidPublicationTransition $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();
                        }
                    }),
                Action::make('dismiss')
                    ->label('Classer sans suite')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (PublicationReport $record) => $record->status === ReportStatus::Open)
                    ->schema([Textarea::make('resolution_note')->label('Note interne')->maxLength(2000)])
                    ->action(function (PublicationReport $record, array $data): void {
                        self::close($record, ReportStatus::Dismissed, $data['resolution_note'] ?? null);
                        Notification::make()->success()->title('Signalement classé')->send();
                    }),
            ]);
    }

    private static function close(PublicationReport $report, ReportStatus $status, ?string $note): void
    {
        $report->forceFill([
            'status' => $status,
            'handled_by' => auth()->id(),
            'handled_at' => now(),
            'resolution_note' => $note,
        ])->save();
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePublicationReports::route('/'),
        ];
    }
}
