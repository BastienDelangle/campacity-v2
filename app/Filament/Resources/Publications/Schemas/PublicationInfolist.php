<?php

namespace App\Filament\Resources\Publications\Schemas;

use App\Models\Publication;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PublicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Contenu')
                    ->columnSpan(2)
                    ->components([
                        TextEntry::make('title')->label('Titre')->weight('bold')->size('lg'),
                        TextEntry::make('description')->label('Description')->prose()->extraAttributes(['class' => 'whitespace-pre-line']),
                        ImageEntry::make('images.path')->label('Photos')->disk('public')->imageHeight(140)->placeholder('Aucune photo'),
                        RepeatableEntry::make('attachments')
                            ->label('Documents')
                            ->placeholder('Aucun document')
                            ->schema([
                                TextEntry::make('original_name')->hiddenLabel()
                                    ->url(fn ($record) => route('publications.attachment', [$record->publication, $record]), shouldOpenInNewTab: true),
                            ]),
                        Section::make('Événement')
                            ->visible(fn (Publication $record) => $record->event !== null)
                            ->columns(2)
                            ->components([
                                TextEntry::make('event.starts_at')->label('Début')->dateTime('d/m/Y H:i'),
                                TextEntry::make('event.ends_at')->label('Fin')->dateTime('d/m/Y H:i')->placeholder('—'),
                                TextEntry::make('event.venue')->label('Lieu')->placeholder('—'),
                                TextEntry::make('event.organizer')->label('Organisateur')->placeholder('—'),
                                TextEntry::make('event.registration_url')->label('Inscription')->url(fn ($state) => $state, true)->placeholder('—'),
                                TextEntry::make('event.program')->label('Programme')->placeholder('—')->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Informations')
                    ->columnSpan(1)
                    ->components([
                        TextEntry::make('status')->label('Statut')->badge(),
                        TextEntry::make('rejection_reason')->label('Motif de refus / suspension')->visible(fn ($state) => filled($state)),
                        TextEntry::make('type.name')->label('Type'),
                        TextEntry::make('category.name')->label('Catégorie'),
                        TextEntry::make('nature')->label('Nature')->badge()->color('gray'),
                        TextEntry::make('origin')->label('Origine')->badge()->color('gray'),
                        TextEntry::make('author.name')->label('Auteur')
                            ->helperText(fn (Publication $record) => $record->author->email.($record->author->is_trusted ? ' · de confiance' : '')),
                        TextEntry::make('organization.name')->label('Organisation')->placeholder('—'),
                        TextEntry::make('commune.name')->label('Commune')->helperText(fn (Publication $record) => $record->address),
                        TextEntry::make('location_precision')->label('Précision publique'),
                        TextEntry::make('submitted_at')->label('Soumise le')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('published_at')->label('Publiée le')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('expires_at')->label('Expire le')->dateTime('d/m/Y')->placeholder('—'),
                    ]),
            ]);
    }
}
