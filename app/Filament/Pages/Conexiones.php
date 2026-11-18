<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\RemoteApi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                        ->helperText('Si la borrás, o queda igual a la del entorno, sigue la del entorno.')
                        ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                            $value = trim((string) $value);
                            if ($value === '') {
                                return;
                            }
                            if (filter_var($value, FILTER_VALIDATE_URL) === false || preg_match('#^https?://#i', $value) !== 1) {
                                $fail('La URL tiene que empezar con http:// o https://.');
                            }
                        })
                        ->maxLength(255),
                    // El token no se muestra ni se copia: el campo arranca vacío (RemoteApi::formState).
                    TextInput::make($service.'_token')
                        ->label('Token nuevo')
                        ->password()
                        ->placeholder('Vacío: queda el que está')
                        ->autocomplete(false)
                        ->extraInputAttributes([
                            'autocomplete' => 'off',
                            'data-1p-ignore' => 'true',
                            'data-lpignore' => 'true',
                            'data-form-type' => 'other',
                        ])
                        ->helperText('Escribilo solo para cambiarlo. Si es igual al del entorno, sigue el del entorno.')
                        ->maxLength(2000),
                    Toggle::make($service.'_clear_token')
                        ->label('Borrar el token del panel')
                        ->helperText('Vuelve a usar el del entorno.')
                        ->visible(fn (): bool => RemoteApi::hasPanelToken($service)),
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
        $saved = true;

        foreach (array_keys(RemoteApi::SERVICES) as $service) {
            $clear = (bool) ($data[$service.'_clear_token'] ?? false);
            $saved = RemoteApi::persist(
                $service,
                is_string($data[$service.'_base_url'] ?? null) ? $data[$service.'_base_url'] : null,
                RemoteApi::tokenToSave(
                    $service,
                    is_string($data[$service.'_token'] ?? null) ? $data[$service.'_token'] : null,
                    $clear,
                ),
                $clear,
            ) && $saved;
        }

        if (! $saved) {
            Notification::make()
                ->danger()
                ->title('No se pudo guardar. Falta la tabla de conexiones.')
                ->send();

            return;
        }

        $this->form->fill(RemoteApi::formState());

        Notification::make()
            ->success()
            ->title('Conexiones guardadas')
            ->send();
    }
}
