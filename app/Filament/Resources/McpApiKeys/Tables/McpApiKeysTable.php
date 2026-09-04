<?php

namespace App\Filament\Resources\McpApiKeys\Tables;

use App\Support\McpServers;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class McpApiKeysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('abilities')
                    ->label('Server MCP')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if (is_string($state)) {
                            return McpServers::label($state);
                        }

                        $id = is_array($state) ? ($state[0] ?? '—') : '—';

                        return McpServers::label((string) $id);
                    }),

                TextColumn::make('tokenable.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_used_at')
                    ->label('Último uso')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Nunca'),

                TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Sin expiración'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Revocar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Revocar seleccionadas'),
                ]),
            ]);
    }
}
