<?php

namespace App\Filament\Pages;

use App\Analytics\Reports;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Analytics: Google-style traffic reports from the cookie-free tracker. */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Analytics';

    protected static ?string $navigationLabel = 'Analytics';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('range')
                ->label('Period')
                ->options(Reports::RANGES)
                ->default('30d')
                ->selectablePlaceholder(false),
        ]);
    }

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 6];
    }
}
