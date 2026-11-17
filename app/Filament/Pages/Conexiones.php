<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\RemoteApi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class Conexiones extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?string $navigationLabel = 'Conexiones';

    protected static ?string $title = 'Conexiones';

    protected static string|UnitEnum|null $navigationGroup = 'MCP';

    protected static ?int $navigationSort = 20;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(RemoteApi::formState());
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];
        foreach (RemoteApi::SERVICES as $service => $meta) {
            $sections[] = Section::make($meta['label'])
                ->description(fn (): string => RemoteApi::hint($service))
                ->columns(2)
                ->schema([
                    TextInput::make($service.'_base_url')
                        ->label('URL')
                        ->helperText('Es la que se está usando. Si la borrás, vuelve la del entorno.')
                        ->maxLength(255),
                    TextInput::make($service.'_token')
                        ->label('Token')
                        ->password()
                        ->revealable()
                        ->copyable(copyMessage: 'Copiado', copyMessageDuration: 1500)
                        ->autocomplete(false)
                        ->extraInputAttributes([
                            'autocomplete' => 'off',
                            'data-1p-ignore' => 'true',
                            'data-lpignore' => 'true',
                            'data-form-type' => 'other',
                        ])
                        ->helperText('El ojo la muestra o la oculta. Si la borrás, vuelve la del entorno.')
                        ->maxLength(2000),
                ]);
        }

        return $schema->components($sections);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Guardar')
                            ->submit('save'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (array_keys(RemoteApi::SERVICES) as $service) {
            RemoteApi::persist(
                $service,
                is_string($data[$service.'_base_url'] ?? null) ? $data[$service.'_base_url'] : null,
                is_string($data[$service.'_token'] ?? null) ? $data[$service.'_token'] : null,
                false,
            );
        }

        $this->form->fill(RemoteApi::formState());

        Notification::make()
            ->success()
            ->title('Conexiones guardadas')
            ->send();
    }
}
