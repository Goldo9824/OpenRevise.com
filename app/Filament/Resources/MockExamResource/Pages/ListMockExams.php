<?php

namespace App\Filament\Resources\MockExamResource\Pages;

use App\Filament\Resources\MockExamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMockExams extends ListRecords
{
    protected static string $resource = MockExamResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
