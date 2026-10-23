<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduled jobs. On LWS, add one cron job from the panel that runs once a
| day:
|
|   php /path/to/openrevise-core/artisan openrevise:analytics-rollup
|
| (or "artisan schedule:run" every minute, which runs it hourly). The
| analytics dashboard also catches up on its own if cron is missing.
*/

Schedule::command('openrevise:analytics-rollup')->hourly();
