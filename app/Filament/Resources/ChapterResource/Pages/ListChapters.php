<?php

namespace App\Filament\Resources\ChapterResource\Pages;

use App\Filament\Resources\ChapterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChapters extends ListRecords
{
    protected static string $resource = ChapterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
