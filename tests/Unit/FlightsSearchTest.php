<?php

namespace Tests\Unit;

use Tests\TestCase;

class FlightsSearchTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->useTable('flights_search_requests');
    }

    public function testRequest()
    {
        $body = '{"segments":[{"departure":{"IATA":"BEG","isCity":false},"arrival":{"IATA":"FCO","isCity":false},"departureDate":"2020-09-15T00:00:00"},{"departure":{"IATA":"FCO","isCity":false},"arrival":{"IATA":"BEG","isCity":false},"departureDate":"2020-09-25T00:00:00"}],"passengers":[{"type":"ADT","count":3},{"type":"CLD","count":2}],"parameters":{"direct":false,"aroundDates":0,"serviceClass":"Economy","flightNumbers":null,"airlines":[],"delayed":true}}';

        $search = $this->json('POST', '/api/flights/search/request', [
            'request' => $body
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'flights' => [
                    'search' => [
                        'formData', 'request', 'results'
                    ],
                ],
                'guide' => [
                    'airports', 'cities', 'countries'
                ]
            ], null)
            ->decodeResponseJson('flights.search');

        $this->assertDatabaseHas('flights_search_requests', ['id' => $search['request']['id']]);

        $this->json('GET', $search['request']['url'])
            ->assertStatus(200);

        $this->json('GET', $search['formData']['url'])
            ->assertStatus(200);
    }
}
