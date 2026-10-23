<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\LessonResource;
use App\Filament\Resources\ReportResource;
use App\Models\Admin\ApiRequest;
use App\Models\Admin\Report;
use App\Models\Content\Lesson;
use App\Models\Content\Status;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** What needs attention: drafts to check, mistakes reported, API activity. */
class ContentAtAGlance extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Content';

    protected function getStats(): array
    {
        $drafts = Lesson::where('status', Status::Draft)->count();
        $reports = Report::where('status', 'new')->count();

        return [
            Stat::make('Published lessons', Lesson::where('status', Status::Published)->count()),
            Stat::make('Drafts to check', $drafts)
                ->color($drafts ? 'warning' : 'gray')
                ->url(LessonResource::getUrl('index', ['activeTab' => 'drafts'])),
            Stat::make('Mistakes reported', $reports)
                ->color($reports ? 'danger' : 'gray')
                ->url(ReportResource::getUrl('index')),
            Stat::make('API calls today', ApiRequest::where('created_at', '>=', today())->count()),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
