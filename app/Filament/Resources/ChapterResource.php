<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChapterResource\Pages;
use App\Filament\Support\ContentOptions;
use App\Models\Content\Chapter;
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

class ChapterResource extends Resource
{
    protected static ?string $model = Chapter::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('topic_id')->label('Topic')->options(ContentOptions::topics())->required()->searchable(),
                TextInput::make('title')->required()->maxLength(160)->placeholder('Cell structure'),
                ContentOptions::slugField(),
                TextInput::make('position')->numeric()->default(0),
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
                TextColumn::make('topic.title')->label('Topic')->description(fn (Chapter $c) => $c->topic->subject->name),
                TextColumn::make('lessons_count')->counts('lessons')->label('Lessons'),
                ContentOptions::statusColumn(),
            ])
            ->filters([SelectFilter::make('topic_id')->label('Topic')->options(ContentOptions::topics())])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([EditAction::make(), ContentOptions::viewOnSiteAction()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChapters::route('/'),
            'create' => Pages\CreateChapter::route('/create'),
            'edit' => Pages\EditChapter::route('/{record}/edit'),
        ];
    }
}
