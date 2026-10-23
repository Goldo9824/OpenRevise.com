<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Content\LessonService;
use App\Filament\Resources\LessonResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** The lesson text goes to its file, not the database: saved through LessonService. */
class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(LessonService::class)->create($data);
    }
}
