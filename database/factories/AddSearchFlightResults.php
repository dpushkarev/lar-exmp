<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\FlightsSearchResult;
use Faker\Generator as Faker;

$factory->define(FlightsSearchResult::class, function (Faker $faker, $params) {
    return [
        'flight_search_request_id' => $params['flight_search_request_id'],
        'price' => 'P7',
        'segments' => ["S4","S2"]
    ];
});
