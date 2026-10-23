<?php

namespace App\Filament\Resources\MockExamResource\RelationManagers;

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

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Questions';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(QuestionForm::fields(mock: true));
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->placeholder('Auto'),
                TextColumn::make('section')->placeholder('-'),
                TextColumn::make('type')->badge()->color('gray')->formatStateUsing(fn ($state) => QuestionType::tryFrom($state)?->label() ?? Str::headline($state)),
                TextColumn::make('question')->limit(80)->wrap(),
                TextColumn::make('marks'),
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
