<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    protected $connection = 'analytics';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;
}
