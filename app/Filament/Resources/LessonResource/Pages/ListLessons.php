<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use App\Models\Content\Status;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'drafts' => Tab::make('Drafts to check')->modifyQueryUsing(fn (Builder $q) => $q->where('status', Status::Draft)),
            'published' => Tab::make('Published')->modifyQueryUsing(fn (Builder $q) => $q->where('status', Status::Published)),
            'hidden' => Tab::make('Hidden')->modifyQueryUsing(fn (Builder $q) => $q->where('status', Status::Hidden)),
        ];
    }
}
