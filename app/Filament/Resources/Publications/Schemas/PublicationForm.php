<?php

namespace App\Filament\Resources\Publications\Schemas;

use App\Enums\LocationPrecision;
use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PublicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Contenu')
                    ->columnSpanFull()
                    ->columns(2)
                    ->components([
                        TextInput::make('title')->label('Titre')->required()->maxLength(150)->columnSpanFull(),
                        Textarea::make('description')->label('Description')->required()->rows(10)->maxLength(10000)->columnSpanFull(),
                        Select::make('publication_type_id')->label('Type')->relationship('type', 'name')->required(),
                        Select::make('category_id')->label('Catégorie')->relationship('category', 'name')->searchable()->preload()->required(),
                        Select::make('nature')->label('Nature')->options(PublicationNature::class)->required(),
                        Select::make('origin')->label('Origine')->options(PublicationOrigin::class)->required(),
                        Select::make('organization_id')->label('Organisation')->relationship('organization', 'name')->searchable()->preload(),
                        Toggle::make('contact_form_enabled')->label('Formulaire de contact')->inline(false),
                    ]),
                Section::make('Localisation')
                    ->columnSpanFull()
                    ->columns(2)
                    ->components([
                        Select::make('commune_id')->label('Commune')->relationship('commune', 'name')->searchable()->preload()->required(),
                        TextInput::make('address')->label('Adresse')->maxLength(255),
                        Select::make('location_precision')->label('Précision affichée')->options(LocationPrecision::class)->required(),
                    ]),
                Section::make('Référencement')
                    ->columnSpanFull()
                    ->collapsed()
                    ->components([
                        TextInput::make('meta_title')->label('Titre SEO')->maxLength(70),
                        Textarea::make('meta_description')->label('Description SEO')->rows(2)->maxLength(170),
                    ]),
            ]);
    }
}
