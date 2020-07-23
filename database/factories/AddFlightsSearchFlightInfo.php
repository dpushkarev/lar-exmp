<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\Models\FlightsSearchFlightInfo::class, function (Faker $faker, $params) {
    return [
        "flight_search_result_id" => $params['flight_search_result_id'],
        'transaction_id' => $faker->uuid
    ];
});
