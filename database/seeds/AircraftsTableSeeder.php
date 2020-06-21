<?php

use Illuminate\Database\Seeder;

class AircraftsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $airCrafts = file(storage_path('seeder_data/RAEQ.TXT'), FILE_IGNORE_NEW_LINES);
        $airCraftsCount = 0;

        foreach ($airCrafts as $airCraft) {
            $explodedLine = explode('","', $airCraft);

            $airCraftsCount += DB::table('aircrafts')->updateOrInsert([
                'code' => $this->cleanString($explodedLine[0]),
            ], [
                'code' => $this->cleanString($explodedLine[0]),
                'name' => $this->cleanString($explodedLine[1]),
                'short_name' => $this->cleanString($explodedLine[2]),
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now()
            ]);

            continue;
        }

        $this->command->getOutput()->writeln("<comment>Insert/Update: {$airCraftsCount} aircrafts</comment>");
    }

    public function cleanString($string)
    {
        $string = str_replace('"', '', $string);
        return mb_strlen($string) === 0 ? null : $string;
    }
}
