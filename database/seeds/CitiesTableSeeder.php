<?php

use Illuminate\Database\Seeder;

class CitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cities = file(storage_path('seeder_data/RCTY.TXT'), FILE_IGNORE_NEW_LINES);

        $originNames = [];

        foreach ($cities as $city) {
            $explodedLine = explode('","', $city);
            if(null === $this->cleanString($explodedLine[1])) {
                $originNames[$this->cleanString($explodedLine[0])] = $this->cleanString($explodedLine[2]);
            }
        }

        foreach ($cities as $city) {
            $explodedLine = explode('","', $city);

            DB::table('cities')->updateOrInsert([
                'code' => $this->cleanString($explodedLine[0]),
                'name' => $this->cleanString($explodedLine[2])
            ], [
                'code' => $this->cleanString($explodedLine[0]),
                'synonym' => $this->cleanString($explodedLine[1]),
                'name' => $this->cleanString($explodedLine[2]),
                'origin_name' => in_array($this->cleanString($explodedLine[1]), ['S', 'C']) ? $originNames[$this->cleanString($explodedLine[0])] : null,
                'country_code' => $this->cleanString($explodedLine[3]),
                'state_code' => $this->cleanString($explodedLine[4]),
                'metro_code' => $this->cleanString($explodedLine[5]),
                'associated_airports' => $this->cleanString($explodedLine[6]),
                'host_service' => $this->cleanString($explodedLine[7]),
                'commercial_service' => $this->cleanString($explodedLine[8]),
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
