<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesRange;
use Filament\Widgets\Widget;

/** How many visitors go from a home page to a lesson to its exam questions. */
class Funnel extends Widget
{
    use UsesRange;

    protected static ?int $sort = 11;

    protected string $view = 'filament.widgets.funnel';

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 2];

    protected function getViewData(): array
    {
        $t = $this->reports()->totals();

        return [
            'range' => $this->rangeLabel(),
            'steps' => [
                ['Home page', $t['funnel_home']],
                ['Then a lesson', $t['funnel_lesson']],
                ['Then its exam questions', $t['funnel_questions']],
            ],
            'max' => max(1, $t['funnel_home']),
        ];
    }
}
