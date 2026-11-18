<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Enums\OrganizationType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Identité')
                    ->columnSpanFull()
                    ->columns(2)
                    ->components([
                        TextInput::make('name')->label('Nom')->required()->maxLength(150),
                        Select::make('type')->label('Type')->options(OrganizationType::class)->required(),
                        Textarea::make('description')->label('Présentation')->rows(6)->maxLength(5000)->columnSpanFull(),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('organizations')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048),
                        Toggle::make('is_active')
                            ->label('Fiche active')
                            ->helperText('Une fiche désactivée n\'est plus visible publiquement.')
                            ->default(true),
                    ]),
                Section::make('Localisation et coordonnées')
                    ->columnSpanFull()
                    ->columns(2)
                    ->components([
                        TextInput::make('address')->label('Adresse')->maxLength(255),
                        Select::make('commune_id')
                            ->label('Commune')
                            ->relationship('commune', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('email')->label('E-mail')->email(),
                        Toggle::make('show_email')->label('E-mail public')->inline(false),
                        TextInput::make('phone')->label('Téléphone')->tel()->maxLength(30),
                        Toggle::make('show_phone')->label('Téléphone public')->inline(false),
                        TextInput::make('website')->label('Site internet')->url()->maxLength(255)->columnSpanFull(),
                        Repeater::make('links')
                            ->label('Liens externes')
                            ->schema([
                                TextInput::make('label')->label('Libellé')->maxLength(60),
                                TextInput::make('url')->label('Adresse')->url()->required()->maxLength(255),
                            ])
                            ->columns(2)
                            ->maxItems(5)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
