<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Request;
use Tests\TestCase;

class SearchTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->useTableWithData('vocabulary_names');
    }
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testAutocomplete()
    {
        $this->json('GET', '/api/guide/autocomplete/iata/mosc/dep')
            ->assertStatus(200);

        $this->json('GET', '/api/guide/autocomplete/iata/mosc/dep/DEM')
            ->assertStatus(200);

        $this->json('GET', '/api/guide/autocomplete/iata/mosc/arr')
            ->assertStatus(200);

        $this->json('GET', '/api/guide/autocomplete/iata/mosc/arr/BEG')
            ->assertStatus(200);

        $this->json('GET', '/api/guide/autocomplete/iata/new y')
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                'guide' => [
                    'autocomplete' => [
                        'iata'
                    ],
                    'countries',
                    'cities',
                    'airports'
                ]
            ]);

        /** check cache */
        $this->assertNotNull($this->autocompleteGetCache(Request::instance()));
    }

    public function testAirlines()
    {
        $this->json('GET', '/api/guide/airlines/all')
            ->assertStatus(200)
            ->assertJsonStructure([
                    'guide' => [
                        "airlines",
                        "countries"
                    ]]
            );

        /** check cache */
        $this->assertNotNull($this->airlinesAllGetCache(Request::instance()));
    }
}
