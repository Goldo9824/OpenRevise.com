<?php

namespace App\Models\Admin;

use Spatie\LaravelPasskeys\Models\Passkey as BasePasskey;

class Passkey extends BasePasskey
{
    protected $connection = 'admin';
}
