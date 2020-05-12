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
        $citiesCount = 0;
        $citiesSynCount = 0;
        $cityWithoutArCount = 0;

        foreach ($cities as $city) {
            $explodedLine = explode('","', $city);

            if (null === $this->cleanString($explodedLine[6])) {
                $cityWithoutArCount++;
                continue;
            }

            if (null === $this->cleanString($explodedLine[1])) {
                $citiesCount += DB::table('cities')->updateOrInsert([
                    'code' => $this->cleanString($explodedLine[0]),
                ], [
                    'code' => $this->cleanString($explodedLine[0]),
                    'name' => $this->cleanString($explodedLine[2]),
                    'country_code' => $this->cleanString($explodedLine[3]),
                    'state_code' => $this->cleanString($explodedLine[4]),
                    'metro_code' => $this->cleanString($explodedLine[5]),
                    'associated_airports' => $this->cleanString($explodedLine[6]),
                    'host_service' => $this->cleanString($explodedLine[7]),
                    'commercial_service' => $this->cleanString($explodedLine[8]),
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now()
                ]);

                continue;
            }

            $citiesSynCount++;
        }

        $this->command->getOutput()->writeln("<comment>Insert/Update: {$citiesCount} cities</comment>");
        $this->command->getOutput()->writeln("<comment>Missed: {$citiesSynCount} synonyms</comment>");
        $this->command->getOutput()->writeln("<comment>Missed: {$cityWithoutArCount} cities without airports</comment>");

    }

    public function cleanString($string)
    {
        $string = str_replace('"', '', $string);
        return mb_strlen($string) === 0 ? null : $string;
    }
}
