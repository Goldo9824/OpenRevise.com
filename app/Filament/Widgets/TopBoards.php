<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Collection;

class TopBoards extends RankedList
{
    protected static ?int $sort = 6;

    protected function heading(): string
    {
        return 'Exam boards (board pages and mock exams)';
    }

    protected function rows(): Collection
    {
        return $this->reports()->breakdown('board');
    }
}
