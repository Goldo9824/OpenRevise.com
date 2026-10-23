<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiKeyResource\Pages;
use App\Models\Admin\ApiKey;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Keys that let Claude use api.openrevise.com. Shown once, stored hashed. */
class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static string|\UnitEnum|null $navigationGroup = 'API';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'API keys';

    protected static ?string $modelLabel = 'API key';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false; // Made from the list page, so the key can be shown once.
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('bold')->description(fn (ApiKey $k) => $k->prefix.'…'),
                TextColumn::make('abilities')->label('Rights')->badge()->color('gray'),
                TextColumn::make('status')->state(fn (ApiKey $k) => $k->status())->badge()
                    ->color(fn ($state) => $state === 'Active' ? 'success' : 'danger'),
                TextColumn::make('last_used_at')->label('Last used')->since()->placeholder('Never'),
                TextColumn::make('expires_at')->label('Expires')->date()->placeholder('Never'),
                TextColumn::make('requests_count')->counts('requests')->label('Requests'),
                TextColumn::make('created_at')->label('Made')->date(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('revoke')->label('Revoke')->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                    ->visible(fn (ApiKey $k) => ! $k->revoked_at)
                    ->requiresConfirmation()
                    ->modalDescription('Anything using this key stops working straight away. This cannot be undone.')
                    ->action(fn (ApiKey $k) => $k->update(['revoked_at' => now()])),
                DeleteAction::make()->visible(fn (ApiKey $k) => (bool) $k->revoked_at),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListApiKeys::route('/')];
    }
}
