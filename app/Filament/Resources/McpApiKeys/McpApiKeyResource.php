<?php

namespace App\Filament\Resources\McpApiKeys;

use App\Filament\Resources\McpApiKeys\Pages\CreateMcpApiKey;
use App\Filament\Resources\McpApiKeys\Pages\ListMcpApiKeys;
use App\Filament\Resources\McpApiKeys\Schemas\McpApiKeyForm;
use App\Filament\Resources\McpApiKeys\Tables\McpApiKeysTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;
use UnitEnum;

class McpApiKeyResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'MCP';

    protected static ?string $navigationLabel = 'API Keys';

    protected static ?string $modelLabel = 'API Key';

    protected static ?string $pluralModelLabel = 'API Keys';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return McpApiKeyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return McpApiKeysTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMcpApiKeys::route('/'),
            'create' => CreateMcpApiKey::route('/create'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
