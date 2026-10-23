<?php

namespace App\Console\Commands;

use App\Analytics\Rollup;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

/** Daily, from cron: roll finished days into totals and delete raw views older than 90 days. */
#[AsCommand(name: 'openrevise:analytics-rollup')]
class AnalyticsRollupCommand extends Command
{
    protected $signature = 'openrevise:analytics-rollup';

    protected $description = 'Roll raw page views up into daily totals and delete raw views past the retention period';

    public function handle(Rollup $rollup): int
    {
        $days = $rollup->catchUp(365);
        $this->components->info("Rolled up $days day(s).");

        return self::SUCCESS;
    }
}
