<?php

namespace App\Filament\Resources\SubjectResource\Pages;

use App\Filament\Resources\SubjectResource;
use App\Filament\Support\ContentOptions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubject extends EditRecord
{
    protected static string $resource = SubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ContentOptions::viewOnSiteAction(),
            DeleteAction::make()->modalDescription('This deletes the subject with all its topics, chapters, lessons, questions and mock exams.'),
        ];
    }
}
