<?php

namespace App\Filament\Resources\McpApiKeys\Pages;

use App\Filament\Resources\McpApiKeys\McpApiKeyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMcpApiKeys extends ListRecords
{
    protected static string $resource = McpApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
