<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Local development: the launch boards and subjects plus a sample of content. */
    public function run(): void
    {
        $this->call([LaunchSeeder::class, DemoContentSeeder::class]);
    }
}
