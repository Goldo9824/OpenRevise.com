<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use App\Models\Admin\ApiKey;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ListApiKeys extends ListRecords
{
    protected static string $resource = ApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New API key')
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    TextInput::make('name')->required()->maxLength(120)->placeholder('Claude: biology lessons'),
                    CheckboxList::make('abilities')->label('Rights')->options(ApiKey::ABILITIES)->default(['read', 'write'])->required(),
                    DatePicker::make('expires_at')->label('Expires on')->minDate(now()->addDay())->helperText('Leave empty for a key that never expires.'),
                ])
                ->action(function (array $data) {
                    [, $plain] = ApiKey::generate($data['name'], $data['abilities'], $data['expires_at'] ?? null);

                    Notification::make()
                        ->title('Copy this key now')
                        ->body(new HtmlString('It will not be shown again. Give it to Claude with the API reference at <a href="'.e(api_url('/')).'" target="_blank" class="underline">'.e(api_url('/')).'</a>.<br><br><code style="user-select: all; word-break: break-all; font-size: 13px;">'.e($plain).'</code>'))
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
