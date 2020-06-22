<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AirlinesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $airlines = file(storage_path('seeder_data/RAIR.TXT'), FILE_IGNORE_NEW_LINES);
        $airlinesJson = file_get_contents(storage_path('seeder_data/airlines.json'));
        $airlinesJson = json_decode($airlinesJson, true);
        $airlinesCount = 0;

        foreach ($airlines as $airline) {
            $explodedLine = explode('","', $airline);
            $logo = null;
            $rating = null;
            $country = null;
            $monochromeLogo = null;
            $colors = null;
            $jsonItem = $airlinesJson[$this->cleanString($explodedLine[0])] ?? null;

            if(null !== $jsonItem) {
                $logo = $jsonItem['logo'] ?? null;
                $rating = $jsonItem['rating'] ?? null;
                $monochromeLogo = $jsonItem['monochromeLogo'] ?? null;
                $country = $jsonItem['countryCode'] ?? null;
                $colors = $jsonItem['colors'] ?? null;
            }

            $airlinesCount += DB::table('airlines')->updateOrInsert([
                'code' => $this->cleanString($explodedLine[0]),
            ], [
                'code' => $this->cleanString($explodedLine[0]),
                'name' => $this->cleanString($explodedLine[1]),
                'short_name' => $this->cleanString($explodedLine[2]),
                'country_code' => $country,
                'participation' => $this->cleanString($explodedLine[3]),
                'vendor_type' => $this->cleanString($explodedLine[4]),
                'logo' => json_encode($logo),
                'rating' => $rating,
                'monochromeLogo' => json_encode($monochromeLogo),
                'colors' => json_encode($colors),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now()
            ]);

            continue;
        }

        $this->command->getOutput()->writeln("<comment>Insert/Update: {$airlinesCount} airlines</comment>");
    }

    public function cleanString($string)
    {
        $string = str_replace('"', '', $string);
        return mb_strlen($string) === 0 ? null : $string;
    }
}
