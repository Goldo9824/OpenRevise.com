<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TopicResource\Pages;
use App\Filament\Support\ContentOptions;
use App\Models\Content\Topic;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('subject_id')->label('Subject')->options(ContentOptions::subjects())->required()->searchable(),
                TextInput::make('title')->required()->maxLength(160)->placeholder('Cell biology'),
                ContentOptions::slugField(),
                TextInput::make('position')->numeric()->default(0)->helperText('Lower numbers come first; topics are numbered in this order.'),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                ContentOptions::statusField(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->weight('bold'),
                TextColumn::make('subject.name')->label('Subject'),
                TextColumn::make('chapters_count')->counts('chapters')->label('Chapters'),
                ContentOptions::statusColumn(),
            ])
            ->filters([SelectFilter::make('subject_id')->label('Subject')->options(ContentOptions::subjects())])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([EditAction::make(), ContentOptions::viewOnSiteAction()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopics::route('/'),
            'create' => Pages\CreateTopic::route('/create'),
            'edit' => Pages\EditTopic::route('/{record}/edit'),
        ];
    }
}
