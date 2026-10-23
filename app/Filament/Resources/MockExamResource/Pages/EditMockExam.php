<?php

namespace App\Filament\Resources\MockExamResource\Pages;

use App\Filament\Resources\MockExamResource;
use App\Filament\Support\ContentOptions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMockExam extends EditRecord
{
    protected static string $resource = MockExamResource::class;

    protected function getHeaderActions(): array
    {
        return [ContentOptions::viewOnSiteAction(), DeleteAction::make()];
    }
}
