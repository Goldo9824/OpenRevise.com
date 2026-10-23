<?php

namespace App\Filament\Support;

use App\Http\Controllers\App\Preview;
use App\Models\Content\Board;
use App\Models\Content\Chapter;
use App\Models\Content\Status;
use App\Models\Content\Subject;
use App\Models\Content\Topic;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/** Form fields and lists shared by the content resources. */
class ContentOptions
{
    /** @return array<int, string> */
    public static function subjects(): array
    {
        return Subject::query()->ordered()->pluck('name', 'id')->all();
    }

    /** @return array<int, string> "Biology › Cell biology" */
    public static function topics(?int $subjectId = null): array
    {
        return Topic::query()
            ->with('subject')
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->orderBy('subject_id')->orderBy('position')
            ->get()
            ->mapWithKeys(fn (Topic $t) => [$t->id => $t->subject->name.' › '.$t->title])
            ->all();
    }

    /** @return array<int, string> "Biology › Cell biology › Cell structure" */
    public static function chapters(): array
    {
        return Chapter::query()
            ->with('topic.subject')
            ->get()
            ->sortBy(fn (Chapter $c) => [$c->topic->subject->position, $c->topic->subject_id, $c->topic->position, $c->position])
            ->mapWithKeys(fn (Chapter $c) => [$c->id => $c->topic->subject->name.' › '.$c->topic->title.' › '.$c->title])
            ->all();
    }

    /** @return array<int, string> */
    public static function boards(): array
    {
        return Board::query()->ordered()->pluck('name', 'id')->all();
    }

    public static function slugField(string $from = 'title'): TextInput
    {
        return TextInput::make('slug')
            ->label('Address (slug)')
            ->maxLength(150)
            ->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
            ->helperText("Lowercase words joined by hyphens. Leave empty to make it from the $from. If you change it, the old address keeps working as a redirect.");
    }

    public static function statusField(string $default = 'published'): Select
    {
        return Select::make('status')
            ->options(Status::options())
            ->default($default)
            ->required()
            ->selectablePlaceholder(false)
            ->helperText('Only published content is shown to students.');
    }

    public static function statusColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->badge()
            ->formatStateUsing(fn (Status $state) => $state->label())
            ->color(fn (Status $state) => match ($state) {
                Status::Published => 'success',
                Status::Draft => 'warning',
                Status::Hidden => 'gray',
            });
    }

    /** "View on site" for published content, "Preview" for drafts. */
    public static function viewOnSiteAction(): Action
    {
        return Action::make('view')
            ->label(fn (Model $record) => ($record->status ?? null) === Status::Published ? 'View on site' : 'Preview')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (Model $record) => ($record->status ?? null) === Status::Published ? app_url($record->path()) : Preview::url($record))
            ->openUrlInNewTab();
    }
}
