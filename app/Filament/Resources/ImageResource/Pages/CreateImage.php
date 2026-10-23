<?php

namespace App\Filament\Resources\ImageResource\Pages;

use App\Content\ImageStore;
use App\Filament\Resources\ImageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CreateImage extends CreateRecord
{
    protected static string $resource = ImageResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $disk = Storage::disk('local');
        $bytes = $disk->get($data['file']);
        $disk->delete($data['file']);

        return app(ImageStore::class)->store($bytes, $data['name'], $data['alt'] ?? null);
    }
}
