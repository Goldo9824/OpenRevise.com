<?php

namespace App\Filament\Resources\LessonResource\RelationManagers;

use App\Filament\Support\QuestionForm;
use App\Models\Content\QuestionType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/** The lesson's batch of exam questions. */
class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Exam questions';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(
            QuestionForm::fields(boards: $this->getOwnerRecord()->boards()->pluck('name', 'boards.id')->all())
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position')->label('#'),
                TextColumn::make('type')->badge()->color('gray')->formatStateUsing(fn ($state) => QuestionType::tryFrom($state)?->label() ?? Str::headline($state)),
                TextColumn::make('question')->limit(90)->wrap(),
                TextColumn::make('marks'),
                TextColumn::make('board.name')->label('Board')->placeholder('All'),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->headerActions([
                CreateAction::make()->modalWidth('3xl')->mutateDataUsing(fn (array $data) => $data + [
                    'position' => (int) $this->getOwnerRecord()->questions()->max('position') + 1,
                ]),
            ])
            ->recordActions([EditAction::make()->modalWidth('3xl'), DeleteAction::make()]);
    }
}
