<?php

namespace App\Filament\Resources\ChapterResource\Pages;

use App\Filament\Resources\ChapterResource;
use App\Filament\Support\ContentOptions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChapter extends EditRecord
{
    protected static string $resource = ChapterResource::class;

    protected function getHeaderActions(): array
    {
        return [ContentOptions::viewOnSiteAction(), DeleteAction::make()];
    }
}
