<?php

use Illuminate\Database\Seeder;

class CountriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $countries = file(storage_path('seeder_data/RCNT.TXT'), FILE_IGNORE_NEW_LINES);

        foreach ($countries as $country) {
            $explodedLine = explode('","', $country);

            DB::table('countries')->updateOrInsert([
                'code' => $this->cleanString($explodedLine[0])
            ], [
                'code' => $this->cleanString($explodedLine[0]),
                'name' => $this->cleanString($explodedLine[1]),
                'currency_code' => $this->cleanString($explodedLine[2]),
                'postal_code' => $this->cleanString($explodedLine[4]),
                'associated' => $this->cleanString($explodedLine[5]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now()
            ]);
        }

    }

    public function cleanString($string)
    {
        $string = str_replace('"', '', $string);
        return mb_strlen($string) === 0 ? null : $string;
    }
}
