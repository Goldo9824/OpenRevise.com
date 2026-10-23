<?php

namespace App\Filament\Resources\ImageResource\Pages;

use App\Content\ImageStore;
use App\Filament\Resources\ImageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EditImage extends EditRecord
{
    protected static string $resource = ImageResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! empty($data['file'])) {
            $disk = Storage::disk('local');
            $bytes = $disk->get($data['file']);
            $disk->delete($data['file']);

            return app(ImageStore::class)->store($bytes, $record->name, $data['alt'] ?? $record->alt, replace: true);
        }

        $record->update(['alt' => $data['alt'] ?? null]);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
