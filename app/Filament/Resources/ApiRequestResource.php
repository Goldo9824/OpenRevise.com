<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiRequestResource\Pages;
use App\Models\Admin\ApiKey;
use App\Models\Admin\ApiRequest;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Every API call, with the key that made it. */
class ApiRequestResource extends Resource
{
    protected static ?string $model = ApiRequest::class;

    protected static string|\UnitEnum|null $navigationGroup = 'API';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Request log';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('apiKey.name')->label('Key')->placeholder('No valid key'),
            TextEntry::make('method'),
            TextEntry::make('path'),
            TextEntry::make('status'),
            TextEntry::make('duration_ms')->suffix(' ms'),
            TextEntry::make('summary')->columnSpanFull()->placeholder('-'),
            TextEntry::make('error')->columnSpanFull()->fontFamily('mono')->placeholder('-'),
            TextEntry::make('ip')->label('IP address'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('j M, H:i:s')->sortable(),
                TextColumn::make('apiKey.name')->label('Key')->placeholder('No valid key'),
                TextColumn::make('method')->badge()->color('gray'),
                TextColumn::make('path')->fontFamily('mono')->size('sm'),
                TextColumn::make('status')->badge()->color(fn (int $state) => match (true) {
                    $state < 300 => 'success',
                    $state < 500 => 'warning',
                    default => 'danger',
                }),
                TextColumn::make('summary')->limit(60)->placeholder('-')->tooltip(fn ($record) => $record->error),
                TextColumn::make('duration_ms')->label('Time')->suffix(' ms')->alignEnd(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('api_key_id')->label('Key')->options(fn () => ApiKey::pluck('name', 'id')),
                Filter::make('failed')->label('Errors only')->query(fn (Builder $query) => $query->where('status', '>=', 400)),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListApiRequests::route('/')];
    }
}
