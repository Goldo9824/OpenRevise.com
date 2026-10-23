<?php

namespace App\Models\Admin;

class ContentChange extends AdminModel
{
    public const UPDATED_AT = null;

    protected $casts = ['changes' => 'array'];
}
