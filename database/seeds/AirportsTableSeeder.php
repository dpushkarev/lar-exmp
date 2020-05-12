<?php

use Illuminate\Database\Seeder;

class AirportsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $airports = file(storage_path('seeder_data/RAPT.TXT'), FILE_IGNORE_NEW_LINES);
        $airportsJson = file_get_contents(storage_path('seeder_data/airports.json'));
        $airportsJson = json_decode($airportsJson, true);
        $airportsCount = 0;
        $airportsSynCount = 0;
        $otherCount = 0;

        foreach ($airports as $airport) {
            $explodedLine = explode('","', $airport);

            if ((int)$this->cleanString($explodedLine[7]) > 3) {
                $otherCount++;
                continue;
            }

            if (null === $this->cleanString($explodedLine[1])) {
                $airportsCount += DB::table('airports')->updateOrInsert([
                    'code' => $this->cleanString($explodedLine[0]),
                ], [
                    'code' => $this->cleanString($explodedLine[0]),
                    'name' => $this->cleanString($explodedLine[2]),
                    'country_code' => $this->cleanString($explodedLine[3]),
                    'state_code' => $this->cleanString($explodedLine[4]),
                    'metro_code' => $this->cleanString($explodedLine[5]),
                    'city_code' => $this->cleanString($explodedLine[6]),
                    'type' => $this->cleanString($explodedLine[7]),
                    'host_service' => $this->cleanString($explodedLine[8]),
                    'latitude' => $airportsJson[$this->cleanString($explodedLine[0])]['lat'] ?? null,
                    'longitude' => $airportsJson[$this->cleanString($explodedLine[0])]['lng'] ?? null
                ]);

                continue;
            }
            $airportsSynCount++;
        }

        $this->command->getOutput()->writeln("<comment>Insert/Update: {$airportsCount} airports</comment>");
        $this->command->getOutput()->writeln("<comment>Missed: {$airportsSynCount} synonyms</comment>");
        $this->command->getOutput()->writeln("<comment>Missed: {$otherCount} others</comment>");

    }

    public function cleanString($string)
    {
        $string = str_replace('"', '', $string);
        return mb_strlen($string) === 0 ? null : $string;
    }
}
