<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Filament\Resources\SubjectResource\RelationManagers\BoardsRelationManager;
use App\Filament\Support\ContentOptions;
use App\Models\Content\Subject;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(120)->placeholder('Biology'),
                ContentOptions::slugField('name'),
                Textarea::make('description')->rows(3)->columnSpanFull()
                    ->helperText('Shown under the subject name and used for search engines.'),
                ColorPicker::make('colour')->helperText('Tints the subject icon.'),
                Select::make('icon')->options(Subject::ICONS)->placeholder('Chosen from the name'),
                ContentOptions::statusField(),
                TextInput::make('position')->numeric()->default(0)->helperText('Lower numbers come first.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->weight('bold'),
                TextColumn::make('boards.name')->badge()->color('gray'),
                TextColumn::make('topics_count')->counts('topics')->label('Topics'),
                ContentOptions::statusColumn(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([EditAction::make(), ContentOptions::viewOnSiteAction()]);
    }

    public static function getRelations(): array
    {
        return [BoardsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
