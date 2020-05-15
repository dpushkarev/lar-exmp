<?php

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
         $this->call([
             CountriesTableSeeder::class,
             CitiesTableSeeder::class,
             AirportsTableSeeder::class,
             VocabularyTableSeeder::class,
             AirlinesTableSeeder::class
         ]);
    }
}
