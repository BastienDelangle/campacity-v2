<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Paramètres métier administrables (valeurs par défaut : config/campacity.php).
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Paramètres';

    protected static ?string $title = 'Paramètres de la plateforme';

    protected static ?string $slug = 'parametres';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::ManageSettings->value) ?? false;
    }

    public function mount(Settings $settings): void
    {
        $this->form->fill($settings->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Publications')
                    ->columns(2)
                    ->components([
                        TextInput::make('publication_duration_days')
                            ->label('Durée de mise en ligne (jours)')
                            ->helperText('Durée avant expiration d\'une publication. Valeur par défaut à valider.')
                            ->numeric()->integer()->minValue(1)->maxValue(730)->required(),
                        TextInput::make('publication_expiration_notice_days')
                            ->label('Rappel avant expiration (jours)')
                            ->numeric()->integer()->minValue(1)->maxValue(60)->required(),
                        TextInput::make('publication_max_images')
                            ->label('Nombre maximal d\'images')
                            ->numeric()->integer()->minValue(1)->maxValue(20)->required(),
                        TextInput::make('publication_max_attachments')
                            ->label('Nombre maximal de documents')
                            ->numeric()->integer()->minValue(0)->maxValue(10)->required(),
                        Toggle::make('publication_edits_require_moderation')
                            ->label('Une modification sensible d\'une publication en ligne repasse en modération')
                            ->helperText('Titre, description, type, catégorie, nature, origine, adresse ou images. Ne s\'applique pas aux utilisateurs de confiance.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getFormContentComponent()]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')->label('Enregistrer')->submit('save'),
                ]),
            ]);
    }

    public function save(Settings $settings): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        $settings->set([
            'publication_duration_days' => (int) $data['publication_duration_days'],
            'publication_expiration_notice_days' => (int) $data['publication_expiration_notice_days'],
            'publication_max_images' => (int) $data['publication_max_images'],
            'publication_max_attachments' => (int) $data['publication_max_attachments'],
            'publication_edits_require_moderation' => (bool) $data['publication_edits_require_moderation'],
        ]);

        Notification::make()->success()->title('Paramètres enregistrés')->send();
    }
}
