<?php

namespace App\Filament\Resources\McpApiKeys\Schemas;

use App\Models\User;
use App\Support\McpServers;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class McpApiKeyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tokenable_id')
                    ->label('Usuario')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->default(fn (): ?int => auth()->id())
                    ->searchable()
                    ->required(),

                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Cursor local — Zalo'),

                Select::make('mcp_server')
                    ->label('Server MCP')
                    ->options(McpServers::all())
                    ->required()
                    ->helperText('La key solo sirve para este endpoint HTTP.'),

                DateTimePicker::make('expires_at')
                    ->label('Expira')
                    ->nullable()
                    ->minDate(now()),
            ]);
    }
}
