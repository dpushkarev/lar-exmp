<?php

namespace Tests\Unit;

use App\Facades\TP;
use Tests\TestCase;

class FlightsSearchTest extends TestCase
{
    const TRANSACTION_ID = 'C8B3F5700A07647789A75EC6238CACE3';

    public function setUp(): void
    {
        parent::setUp();
        $this->useTable('flights_search_requests');
        $this->useTable('flights_search_results');
        $this->useTableWithData('aircrafts');

        TP::shouldReceive('LowFareSearchReq')
            ->once()
            ->andReturn(unserialize(file_get_contents(__DIR__ . '/files/LowFareSearchRsp.txt')));

    }

    public function testFlightsSearch()
    {
        $body = file_get_contents(__DIR__ . '/files/LowFareSearchReq.txt');

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
                    'airports' => [
                        'BEG', 'FCO'
                    ],
                    'cities',
                    'countries' => [
                        'RS', 'IT'
                    ]
                ],
                'system'
            ], null)
            ->assertJsonCount(2, 'flights.search.request.segments')
            ->assertJsonCount(2, 'flights.search.request.passengers')
            ->assertJsonCount(2, 'guide.cities')
            ->decodeResponseJson('flights.search');

        $this->assertDatabaseHas('flights_search_requests', ['id' => $search['request']['id']]);

        $this->json('GET', $search['request']['url'])
            ->assertStatus(200);

        $this->json('GET', $search['formData']['url'])
            ->assertStatus(200);

        $this->json('POST', $search['results']['url'])
            ->assertStatus(200)
            ->assertJsonStructure([
                'flights' => [
                    'search' => [
                        'formData', 'request', 'results', 'resultData'
                    ],
                ],
                'guide' => [
                    'airports' => [
                        'BEG', 'FCO'
                    ],
                    'cities',
                    'countries' => [
                        'RS', 'IT'
                    ],
                    'aircrafts'
                ],
                'system'
            ], null)
            ->assertJsonCount(2, 'flights.search.request.segments')
            ->assertJsonCount(2, 'flights.search.request.passengers')
            ->assertJsonCount(2, 'guide.cities')
            ->assertJsonCount(2, 'guide.airports')
            ->assertJsonCount(2, 'guide.cities')
            ->assertJsonCount(2, 'guide.countries')
            ->assertJsonCount(2, 'guide.aircrafts')
            ->assertJsonCount(2, 'flights.search.results.flightGroups')
            ->assertJsonCount(2, 'flights.search.results.flightGroups.0.segments')
            ->assertJsonCount(2, 'flights.search.results.flightGroups.1.segments')
            ->assertJsonCount(3, 'flights.search.results.groupsData.segments')
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices')
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.P1.passengerFares')
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.flightPrice.amount', 41739)
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.totalPrice.amount', 41839.5)
            ->assertJsonPath('flights.search.results.groupsData.prices.P2.flightPrice.amount', 58371)
            ->assertJsonPath('flights.search.results.groupsData.prices.P2.totalPrice.amount', 58471.5)
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.P2.passengerFares');

        $this->assertDatabaseHas('flights_search_requests', ['id' => $search['request']['id'], 'transaction_id' => static::TRANSACTION_ID]);
        $this->assertDatabaseHas('flights_search_results', ['transaction_id' => static::TRANSACTION_ID, 'price' => 'P1', 'segments' => '["S1","S2"]']);
        $this->assertDatabaseHas('flights_search_results', ['transaction_id' => static::TRANSACTION_ID, 'price' => 'P2', 'segments' => '["S3","S2"]']);

        $this->json('GET', $search['results']['url'])
            ->assertStatus(200);
    }
}
