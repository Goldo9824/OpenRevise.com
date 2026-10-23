<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Filament\Resources\LessonResource\RelationManagers\QuestionsRelationManager;
use App\Filament\Support\ContentOptions;
use App\Filament\Support\QuestionForm;
use App\Models\Content\Course;
use App\Models\Content\Lesson;
use App\Models\Content\Status;
use App\Models\Content\Tier;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        $drafts = Lesson::where('status', Status::Draft)->count();

        return $drafts ? (string) $drafts : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Drafts waiting to be checked';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Lesson')->columnSpan(2)->schema([
                Select::make('chapter_id')->label('Chapter')->options(ContentOptions::chapters())->required()->searchable(),
                Grid::make(2)->schema([
                    TextInput::make('title')->required()->maxLength(200),
                    ContentOptions::slugField(),
                ]),
                TextInput::make('summary')->maxLength(300)
                    ->helperText('One or two sentences under the title, also used by search engines.'),
                Textarea::make('body')
                    ->label('Lesson text')
                    ->rows(28)
                    ->required()
                    ->extraInputAttributes(['style' => 'font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 13px; line-height: 1.6;'])
                    ->rules([QuestionForm::textRule()])
                    ->helperText(new HtmlString('Plain text with tags: <code>## Heading</code>, <code>[KEYTERM] Term: definition</code>, <code>[TABLE]</code> rows split by | <code>[/TABLE]</code>, <code>[IMAGE] name | Caption</code>, <code>[FORMULA] ...</code>, <code>[TIP] ...</code>, <code>- bullets</code>. Broken tags are refused with their line number.')),
            ]),
            Section::make('Who it is for')->columnSpan(1)->schema([
                ContentOptions::statusField('draft'),
                CheckboxList::make('boards')->label('Exam boards')->options(ContentOptions::boards())->required()
                    ->helperText('Share a lesson only when the boards ask for the same thing.'),
                Radio::make('tier')->options(Tier::options())->default('all')->required(),
                Radio::make('course')->options(Course::options())->default('all')->required(),
                TextInput::make('position')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['boards', 'chapter.topic.subject']))
            ->columns([
                TextColumn::make('title')->searchable()->weight('bold')->wrap()
                    ->description(fn (Lesson $l) => $l->chapter->topic->subject->name.' › '.$l->chapter->topic->title.' › '.$l->chapter->title),
                TextColumn::make('boards.name')->badge()->color('gray'),
                TextColumn::make('tags')->label('Tags')->badge()->color('warning')
                    ->state(fn (Lesson $l) => array_values(array_filter([
                        $l->tier === Tier::Higher ? 'Higher only' : null,
                        $l->course === Course::Separate ? 'Separate only' : null,
                    ]))),
                ContentOptions::statusColumn(),
                TextColumn::make('questions_count')->counts('questions')->label('Questions')->alignCenter(),
                TextColumn::make('updated_at')->label('Updated')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(Status::options()),
                SelectFilter::make('subject')->options(ContentOptions::subjects())
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->whereHas('chapter.topic', fn ($t) => $t->where('subject_id', $data['value'])) : $query),
                SelectFilter::make('board')->options(ContentOptions::boards())
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->forBoard((int) $data['value']) : $query),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([EditAction::make(), ContentOptions::viewOnSiteAction()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')->label('Publish')->icon(Heroicon::OutlinedCheckCircle)->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => Status::Published]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('draft')->label('Move back to draft')->icon(Heroicon::OutlinedPencilSquare)
                        ->action(fn (Collection $records) => $records->each->update(['status' => Status::Draft]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('hide')->label('Hide')->icon(Heroicon::OutlinedEyeSlash)->color('gray')
                        ->action(fn (Collection $records) => $records->each->update(['status' => Status::Hidden]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [QuestionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
