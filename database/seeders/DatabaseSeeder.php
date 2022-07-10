<?php

namespace Database\Seeders;

use App\Models\CountryCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        $codes = [
            [
                'code' => 10415971874,
                'name' => 'KZ'
            ],
            [
                'code' => 10424076448,
                'name' => 'UA'
            ],
        ];

        CountryCode::insert($codes);
    }
}
