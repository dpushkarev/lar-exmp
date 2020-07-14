<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\FlightsSearchRequest;
use Faker\Generator as Faker;

$factory->define(FlightsSearchRequest::class, function (Faker $faker, $params) {
    return [
        "data" => $params['data'],
        'transaction_id' => $faker->uuid
    ];
});
