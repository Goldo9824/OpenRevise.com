<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentChangeResource\Pages;
use App\Models\Admin\ContentChange;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/** Who changed which piece of content, and what changed. */
class ContentChangeResource extends Resource
{
    protected static ?string $model = ContentChange::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Logs';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Change history';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('causer')->label('By'),
            TextEntry::make('subject_type')->label('What')->formatStateUsing(fn ($state, $record) => Str::headline($state).' #'.$record->subject_id),
            TextEntry::make('event'),
            TextEntry::make('subject_label')->label('Title')->columnSpanFull(),
            KeyValueEntry::make('changes')->columnSpanFull()
                ->state(fn ($record) => collect($record->changes ?? [])->map(fn ($c) => json_encode($c['from'] ?? null, JSON_UNESCAPED_UNICODE).'  →  '.json_encode($c['to'] ?? null, JSON_UNESCAPED_UNICODE))->all()),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('j M Y, H:i')->sortable(),
                TextColumn::make('causer')->label('By'),
                TextColumn::make('event')->badge()->color(fn ($state) => match ($state) {
                    'created' => 'success',
                    'deleted' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('subject_type')->label('What')->formatStateUsing(fn ($state) => Str::headline($state)),
                TextColumn::make('subject_label')->label('Title')->limit(60),
                TextColumn::make('changes')->label('Fields')->state(fn ($record) => implode(', ', array_keys($record->changes ?? [])))->placeholder('-')->limit(40),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('subject_type')->label('What')->options(fn () => ContentChange::query()->distinct()->pluck('subject_type', 'subject_type')->map(fn ($t) => Str::headline($t))),
                SelectFilter::make('event')->options(['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted']),
            ])
            ->recordActions([ViewAction::make()->modalWidth('3xl')]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListContentChanges::route('/')];
    }
}
