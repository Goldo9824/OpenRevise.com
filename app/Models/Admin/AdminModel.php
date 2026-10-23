<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

abstract class AdminModel extends Model
{
    protected $connection = 'admin';

    protected $guarded = ['id'];
}
