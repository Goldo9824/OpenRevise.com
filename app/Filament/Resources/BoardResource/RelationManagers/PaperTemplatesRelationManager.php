<?php

namespace App\Filament\Resources\BoardResource\RelationManagers;

use App\Filament\Support\ContentOptions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The shape of each real paper: time, marks, front-cover text and sections.
 * A mock exam picks one, so nobody types the time and marks again.
 */
class PaperTemplatesRelationManager extends RelationManager
{
    protected static string $relationship = 'paperTemplates';

    protected static ?string $title = 'Paper templates';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                Select::make('subject_id')->label('Subject')->options(ContentOptions::subjects())->required(),
                TextInput::make('name')->required()->maxLength(120)->placeholder('Paper 1 Higher Tier'),
                TextInput::make('slug')->maxLength(120)->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')->helperText('Leave empty to make it from the name. The API refers to templates by this.'),
                Select::make('tier')->options(['all' => 'One paper for all', 'foundation' => 'Foundation tier', 'higher' => 'Higher tier'])->default('all')->required(),
                TextInput::make('duration_minutes')->label('Time allowed (minutes)')->numeric()->minValue(5)->required(),
                TextInput::make('total_marks')->numeric()->minValue(1)->required(),
            ]),
            Textarea::make('instructions')->rows(3)->helperText('Front cover instructions, one per line.'),
            Textarea::make('information')->rows(3)->helperText('Front cover information, one per line.'),
            Repeater::make('sections')->schema([
                TextInput::make('title')->required(),
                TextInput::make('description'),
            ])->columns(2)->defaultItems(0)->collapsible(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('subject.name')->label('Subject'),
                TextColumn::make('name')->weight('bold')->description(fn ($record) => $record->slug),
                TextColumn::make('duration_minutes')->label('Time')->formatStateUsing(fn ($state, $record) => $record->durationLabel()),
                TextColumn::make('total_marks')->label('Marks'),
                TextColumn::make('mock_exams_count')->counts('mockExams')->label('Mock exams'),
            ])
            ->filters([SelectFilter::make('subject_id')->label('Subject')->options(ContentOptions::subjects())])
            ->headerActions([CreateAction::make()->modalWidth('3xl')])
            ->recordActions([EditAction::make()->modalWidth('3xl'), DeleteAction::make()]);
    }
}
