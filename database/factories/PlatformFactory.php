<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\User;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(\App\Models\FrontendDomain::class, function (Faker $faker) {
    return [
        'domain' => $faker->domainName,
        'travel_agency_id' => $faker->randomNumber(),
        'currency_code' => 'RSD',
        'agency_fee_default' => 495,
        'token' => '06e7140bbbbad363108b45c889e89099'
    ];
});

