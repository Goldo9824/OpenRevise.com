<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoardResource\Pages;
use App\Filament\Resources\BoardResource\RelationManagers\PaperTemplatesRelationManager;
use App\Models\Content\Board;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Exam boards and the paper templates mock exams are built on. */
class BoardResource extends Resource
{
    protected static ?string $model = Board::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Boards and papers';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(60)->placeholder('AQA'),
                TextInput::make('slug')->required()->maxLength(60)->regex('/^[a-z0-9-]+$/')->unique(ignoreRecord: true)
                    ->helperText('Used in addresses like /gcse/biology/aqa/, so no topic can use it.'),
                TextInput::make('full_name')->maxLength(255)->placeholder('Pearson Edexcel'),
                TextInput::make('website')->url()->maxLength(255),
                TextInput::make('position')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('bold'),
                TextColumn::make('slug'),
                TextColumn::make('subjects_count')->counts('subjects')->label('Subjects'),
                TextColumn::make('paper_templates_count')->counts('paperTemplates')->label('Paper templates'),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [PaperTemplatesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoards::route('/'),
            'create' => Pages\CreateBoard::route('/create'),
            'edit' => Pages\EditBoard::route('/{record}/edit'),
        ];
    }
}
