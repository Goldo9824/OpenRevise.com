<?php

namespace App\Filament\Widgets;

use App\Models\Content\Subject;
use Illuminate\Support\Collection;

class TopSubjects extends RankedList
{
    protected static ?int $sort = 5;

    protected function heading(): string
    {
        return 'Subjects';
    }

    protected function rows(): Collection
    {
        $names = Subject::pluck('name', 'slug');

        return $this->reports()->breakdown('subject')->map(fn ($r) => ['label' => $names[$r['label']] ?? $r['label']] + $r);
    }
}
