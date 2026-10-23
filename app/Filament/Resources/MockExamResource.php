<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MockExamResource\Pages;
use App\Filament\Resources\MockExamResource\RelationManagers\QuestionsRelationManager;
use App\Filament\Support\ContentOptions;
use App\Models\Content\MockExam;
use App\Models\Content\PaperTemplate;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MockExamResource extends Resource
{
    protected static ?string $model = MockExam::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('subject_id')->label('Subject')->options(ContentOptions::subjects())->required()->live(),
                Select::make('board_id')->label('Exam board')->options(ContentOptions::boards())->required()->live(),
                Select::make('paper_template_id')->label('Paper template')->required()
                    ->options(fn (Get $get) => PaperTemplate::query()
                        ->where('subject_id', $get('subject_id'))->where('board_id', $get('board_id'))
                        ->get()->mapWithKeys(fn ($t) => [$t->id => "{$t->name} ({$t->durationLabel()}, {$t->total_marks} marks)"]))
                    ->helperText('The time and total marks come from the template. Add templates under Boards.'),
                Select::make('topic_id')->label('Topic (optional)')
                    ->options(fn (Get $get) => ContentOptions::topics($get('subject_id') ? (int) $get('subject_id') : null))
                    ->placeholder('Whole subject'),
                TextInput::make('title')->required()->maxLength(200)->placeholder('AQA Biology Paper 1 (Higher), Set A'),
                ContentOptions::slugField(),
                Textarea::make('description')->rows(2)->columnSpanFull(),
                ContentOptions::statusField('draft'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['subject', 'board', 'paperTemplate']))
            ->columns([
                TextColumn::make('title')->searchable()->weight('bold')
                    ->description(fn (MockExam $e) => $e->subject->name.' · '.$e->board->name.' · '.$e->paperTemplate->name),
                TextColumn::make('questions_count')->counts('questions')->label('Questions'),
                TextColumn::make('questions_sum_marks')->sum('questions', 'marks')->label('Marks')
                    ->formatStateUsing(fn ($state, MockExam $e) => (int) $state.' / '.$e->paperTemplate->total_marks)
                    ->color(fn ($state, MockExam $e) => (int) $state === $e->paperTemplate->total_marks ? null : 'warning'),
                ContentOptions::statusColumn(),
            ])
            ->filters([
                SelectFilter::make('subject_id')->label('Subject')->options(ContentOptions::subjects()),
                SelectFilter::make('board_id')->label('Board')->options(ContentOptions::boards()),
            ])
            ->recordActions([EditAction::make(), ContentOptions::viewOnSiteAction()]);
    }

    public static function getRelations(): array
    {
        return [QuestionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMockExams::route('/'),
            'create' => Pages\CreateMockExam::route('/create'),
            'edit' => Pages\EditMockExam::route('/{record}/edit'),
        ];
    }
}
