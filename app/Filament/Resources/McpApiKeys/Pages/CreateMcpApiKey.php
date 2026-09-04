<?php

namespace App\Filament\Resources\McpApiKeys\Pages;

use App\Filament\Resources\McpApiKeys\McpApiKeyResource;
use App\Models\User;
use App\Support\McpServers;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Events\RecordCreated;
use Filament\Resources\Events\RecordSaved;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class CreateMcpApiKey extends CreateRecord
{
    protected static string $resource = McpApiKeyResource::class;

    protected ?string $plainTextToken = null;

    public ?string $newPlainToken = null;

    protected function handleRecordCreation(array $data): PersonalAccessToken
    {
        /** @var User $user */
        $user = User::query()->findOrFail($data['tokenable_id']);

        $serverId = (string) $data['mcp_server'];
        McpServers::assertValid($serverId);

        $tokenResult = $user->createToken(
            $data['name'],
            [$serverId],
            isset($data['expires_at']) && $data['expires_at']
                ? new \DateTime($data['expires_at'])
                : null
        );

        $this->plainTextToken = $tokenResult->plainTextToken;

        return $tokenResult->accessToken;
    }

    public function showNewToken(): Action
    {
        return Action::make('showNewToken')
            ->label('Token generado')
            ->icon('heroicon-o-key')
            ->color('success')
            ->modalHeading('API Key — cópiala ahora')
            ->modalDescription('Este secreto solo se muestra una vez. Si lo pierdes, genera una key nueva.')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->schema([
                TextInput::make('token')
                    ->label('Bearer token')
                    ->default(fn (): string => $this->newPlainToken ?? '')
                    ->readOnly()
                    ->copyable()
                    ->extraInputAttributes([
                        'onclick' => 'this.select()',
                        'style' => 'font-family: monospace; font-size: 0.82rem;',
                    ]),
            ])
            ->modalSubmitActionLabel('Listo, ya la copié')
            ->modalCancelAction(false)
            ->action(function (): void {
                $this->newPlainToken = null;

                $url = static::getResource()::getUrl('index');
                $this->redirect($url, navigate: FilamentView::hasSpaMode($url));
            });
    }

    public function create(bool $another = false): void
    {
        if ($this->isCreating) {
            return;
        }

        $this->isCreating = true;

        $this->authorizeAccess();

        if ($another) {
            $preserveRawState = $this->preserveFormDataWhenCreatingAnother($this->form->getRawState());
        }

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeCreate($data);

            $this->callHook('beforeCreate');

            $this->record = $this->handleRecordCreation($data);

            $this->form->model($this->getRecord())->saveRelationships();

            $this->callHook('afterCreate');
            Event::dispatch(RecordCreated::class, ['record' => $this->record, 'data' => $data, 'page' => $this]);
            Event::dispatch(RecordSaved::class, ['record' => $this->record, 'data' => $data, 'page' => $this]);
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            $this->isCreating = false;

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            $this->isCreating = false;

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        $this->rememberData();

        $this->getCreatedNotification()?->send();

        if ($another) {
            $this->form->model($this->getRecord()::class);
            $this->record = null;

            $this->fillForm();

            $this->form->rawState([
                ...$this->form->getRawState(),
                ...$preserveRawState,
            ]);

            $this->isCreating = false;

            return;
        }

        $this->newPlainToken = $this->plainTextToken ?? '';

        $this->form->model($this->getRecord()::class);
        $this->record = null;
        $this->fillForm();

        $this->isCreating = false;

        $this->mountAction('showNewToken');
    }
}
