<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

class DailyTotal extends Model
{
    protected $connection = 'analytics';

    protected $primaryKey = 'date';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
