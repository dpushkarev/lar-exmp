<?php

use Illuminate\Database\Seeder;

class VocabularyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cities = file(storage_path('seeder_data/RCTY.TXT'), FILE_IGNORE_NEW_LINES);
        $airports = file(storage_path('seeder_data/RAPT.TXT'), FILE_IGNORE_NEW_LINES);
        $citiesTranslated = file(storage_path('seeder_data/rs.csv'), FILE_IGNORE_NEW_LINES);

        $cityIds = DB::table('cities')->select(['id', 'code'])->get();
        $cityIds = $cityIds->pluck('id', 'code');
        $citiesVocCount = 0;
        $citiesMissed = 0;
        $citiesLang = 0;
        $citiesLangMissed = 0;

        $airPortIds = DB::table('airports')->select(['id', 'code'])->get();
        $airPortIds = $airPortIds->pluck('id', 'code');
        $airportsVocCount = 0;
        $airportsMissed = 0;

        if ($cityIds->isNotEmpty() && $airPortIds->isNotEmpty()) {
            DB::table('vocabulary_names')->delete();
        }

        foreach ($cities as $city) {
            $explodedLine = explode('","', $city);

            $cityId = $cityIds->get($this->cleanString($explodedLine[0]));

            if (null === $cityId) {
                $citiesMissed++;
                continue;
            }

            $citiesVocCount += DB::table('vocabulary_names')->insert([
                'synonym' => $this->cleanString($explodedLine[1]),
                'name' => $this->cleanString($explodedLine[2]),
                'nameable_id' => $cityId,
                'nameable_type' => \App\Models\City::class
            ]);
        }

        foreach ($airports as $airport) {
            $explodedLine = explode('","', $airport);

            $airportId = $airPortIds->get($this->cleanString($explodedLine[0]));

            if (null === $airportId) {
                $airportsMissed++;
                continue;
            }

            $airportsVocCount += DB::table('vocabulary_names')->insert([
                'synonym' => $this->cleanString($explodedLine[1]),
                'name' => $this->cleanString($explodedLine[2]),
                'nameable_id' => $airportId,
                'nameable_type' => \App\Models\Airport::class
            ]);
        }

        foreach ($citiesTranslated as $line) {
            $ar = explode(',', $line);
            $cityId = $cityIds->get($this->cleanString($ar[0]));

            if (null === $cityId) {
                $citiesLangMissed++;
                continue;
            }

            $citiesLang += DB::table('vocabulary_names')->insert([
                'name' => $this->cleanString($ar[1]),
                'nameable_id' => $cityId,
                'nameable_type' => \App\Models\City::class,
                'lang' => 'rs'
            ]);

        }

        $this->command->getOutput()->writeln("<comment>Insert: {$citiesVocCount} cities + synonyms</comment>");
        $this->command->getOutput()->writeln("<comment>Insert: {$citiesLang} translated cities</comment>");
        $this->command->getOutput()->writeln("<comment>Insert: {$airportsVocCount} airports + synonyms</comment>");
        $this->command->getOutput()->writeln("<comment>Missed: {$citiesMissed} cities without nameable_id</comment>");
        $this->command->getOutput()->writeln("<comment>Missed: {$airportsMissed} airports without nameable_id</comment>");
        $this->command->getOutput()->writeln("<comment>Missed: {$citiesLangMissed} translated cities</comment>");
    }

    public function cleanString($string)
    {
        $string = str_replace('"', '', $string);
        return mb_strlen($string) === 0 ? null : $string;
    }
}
