<?php

namespace App\Filament\Resources\ErrorLogResource\Pages;

use App\Filament\Resources\ErrorLogResource;
use App\Models\Admin\ErrorLog;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListErrorLogs extends ListRecords
{
    protected static string $resource = ErrorLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear')->label('Clear all')->color('gray')->requiresConfirmation()
                ->action(fn () => ErrorLog::query()->delete()),
        ];
    }
}
