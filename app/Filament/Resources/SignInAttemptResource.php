<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SignInAttemptResource\Pages;
use App\Models\Admin\SignInAttempt;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SignInAttemptResource extends Resource
{
    protected static ?string $model = SignInAttempt::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Logs';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?string $navigationLabel = 'Sign-ins';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('j M Y, H:i')->sortable(),
                IconColumn::make('succeeded')->boolean(),
                TextColumn::make('method')->badge()->color('gray'),
                TextColumn::make('email')->placeholder('-'),
                TextColumn::make('ip')->label('IP address'),
                TextColumn::make('user_agent')->label('Browser')->limit(50)->tooltip(fn ($state) => $state),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([TernaryFilter::make('succeeded')]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSignInAttempts::route('/')];
    }
}
