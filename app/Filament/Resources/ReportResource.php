<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Admin\Report;
use App\Models\Content\Lesson;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/** Mistakes reported by students from lesson pages. */
class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Mistake reports';

    protected static ?int $navigationSort = 8;

    public static function getNavigationBadge(): ?string
    {
        $new = Report::where('status', 'new')->count();

        return $new ? (string) $new : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Sent')->since()->sortable(),
                TextColumn::make('message')->wrap()->limit(200),
                TextColumn::make('page_url')->label('Page')->limit(45)->url(fn ($state) => $state, true)->color('primary'),
                TextColumn::make('status')->badge()->color(fn ($state) => $state === 'new' ? 'danger' : 'success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([SelectFilter::make('status')->options(['new' => 'New', 'resolved' => 'Resolved'])->default('new')])
            ->recordActions([
                Action::make('lesson')->label('Edit lesson')->icon(Heroicon::OutlinedPencilSquare)->color('gray')
                    ->visible(fn (Report $r) => $r->lesson_id && Lesson::whereKey($r->lesson_id)->exists())
                    ->url(fn (Report $r) => LessonResource::getUrl('edit', ['record' => $r->lesson_id])),
                Action::make('resolve')->label('Mark fixed')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Report $r) => $r->status === 'new')
                    ->action(fn (Report $r) => $r->update(['status' => 'resolved'])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('resolve')->label('Mark fixed')->action(fn (Collection $records) => $records->each->update(['status' => 'resolved'])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListReports::route('/')];
    }
}
