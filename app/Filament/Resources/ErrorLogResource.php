<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ErrorLogResource\Pages;
use App\Models\Admin\ErrorLog;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ErrorLogResource extends Resource
{
    protected static ?string $model = ErrorLog::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Logs';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Errors';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('exception'),
            TextEntry::make('message')->columnSpanFull(),
            TextEntry::make('url')->label('Address')->columnSpanFull(),
            TextEntry::make('file')->formatStateUsing(fn ($state, $record) => $state.':'.$record->line)->columnSpanFull(),
            TextEntry::make('trace')->columnSpanFull()->fontFamily('mono')->size('xs')
                ->formatStateUsing(fn ($state) => nl2br(e($state)))->html(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('j M, H:i:s')->sortable(),
                TextColumn::make('exception')->formatStateUsing(fn ($state) => class_basename((string) $state))->badge()->color('danger'),
                TextColumn::make('message')->limit(90)->wrap(),
                TextColumn::make('url')->label('Address')->limit(40),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make()->modalWidth('5xl')]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListErrorLogs::route('/')];
    }
}
