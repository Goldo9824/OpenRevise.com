<?php

namespace App\Models\Content;

use App\Models\Concerns\RecordsChanges;
use Illuminate\Database\Eloquent\Model;

abstract class ContentModel extends Model
{
    use RecordsChanges;

    protected $connection = 'content';

    protected $guarded = ['id'];
}
