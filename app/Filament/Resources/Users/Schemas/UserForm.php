<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Compte')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('name')->label('Nom')->required()->maxLength(255),
                        TextInput::make('email')->label('E-mail')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                        DateTimePicker::make('email_verified_at')->label('E-mail vérifié le')->native(false),
                        TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'Laisser vide pour conserver le mot de passe actuel.' : null),
                    ]),
                Section::make('Coordonnées')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('phone')->label('Téléphone')->tel()->maxLength(30),
                        TextInput::make('website')->label('Site internet')->url()->maxLength(255),
                        Toggle::make('show_email')->label('E-mail public'),
                        Toggle::make('show_phone')->label('Téléphone public'),
                        Toggle::make('show_website')->label('Site public'),
                    ]),
                Section::make('Rôles')
                    ->columnSpanFull()
                    ->visible(fn (?Model $record) => auth()->user()->can('assignRoles', $record ?? new User))
                    ->components([
                        CheckboxList::make('roles')
                            ->label('')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Model $record) => Role::tryFrom($record->name)?->label() ?? $record->name)
                            ->columns(3),
                    ]),
            ]);
    }
}
