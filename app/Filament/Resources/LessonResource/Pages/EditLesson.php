<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Content\LessonService;
use App\Filament\Resources\LessonResource;
use App\Filament\Support\ContentOptions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['body'] = $this->record->body();
        $data['boards'] = $this->record->boards()->pluck('boards.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(LessonService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [ContentOptions::viewOnSiteAction(), DeleteAction::make()];
    }
}
