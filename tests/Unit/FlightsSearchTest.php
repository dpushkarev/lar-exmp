<?php

namespace Tests\Unit;

use App\Facades\TP;
use App\Models\FlightsSearchResult;
use Tests\TestCase;

class FlightsSearchTest extends TestCase
{
    protected $agencyChargeAmount = 100.5;

    public function setUp(): void
    {
        parent::setUp();
        $this->useTable('flights_search_requests');
        $this->useTable('flights_search_results');
        $this->useTableWithData('aircrafts');

    }

    public function testFlightsSearchDirect()
    {
        $transaction_id = 'C8B3F5700A07647789A75EC6238CACE3';
        $countOfPassenger = 3;

        TP::shouldReceive('LowFareSearchReq')
            ->once()
            ->andReturn(unserialize(file_get_contents(__DIR__ . '/files/LowFareSearchRsp.txt')));

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
            ->assertJsonCount(2, 'guide.countries')
            ->assertJsonCount(2, 'guide.aircrafts')
            ->assertJsonCount(2, 'flights.search.results.flightGroups')
            ->assertJsonCount(2, 'flights.search.results.flightGroups.0.segments')
            ->assertJsonCount(2, 'flights.search.results.flightGroups.1.segments')
            ->assertJsonCount(3, 'flights.search.results.groupsData.segments')
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices')
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.P1.passengerFares')
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.flightPrice.amount', 41739)
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.totalPrice.amount', 41739 + $this->agencyChargeAmount * $countOfPassenger)
            ->assertJsonPath('flights.search.results.groupsData.prices.P2.flightPrice.amount', 58371)
            ->assertJsonPath('flights.search.results.groupsData.prices.P2.totalPrice.amount', 58371 + $this->agencyChargeAmount * $countOfPassenger)
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.P2.passengerFares');

        $this->assertDatabaseHas('flights_search_requests', ['id' => $search['request']['id'], 'transaction_id' => $transaction_id]);
        $this->assertDatabaseHas('flights_search_results', ['request_id' => $search['request']['id'], 'price' => 'P1', 'segments' => '["S1","S2"]']);
        $this->assertDatabaseHas('flights_search_results', ['request_id' => $search['request']['id'], 'price' => 'P2', 'segments' => '["S3","S2"]']);

        $this->assertEquals(2, FlightsSearchResult::all()->count());

        $this->json('GET', $search['results']['url'])
            ->assertStatus(200);
    }

    public function testFlightsSearchConnecting ()
    {
        $transaction_id = 'D7B751950A076478474E55721925C6F6';
        $countOfPassenger = 3;

        TP::shouldReceive('LowFareSearchReq')
            ->once()
            ->andReturn(unserialize(file_get_contents(__DIR__ . '/files/LowFareSearchRsp2.txt')));

        $body = file_get_contents(__DIR__ . '/files/LowFareSearchReq2.txt');

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
                        'BEG', 'TIV',
                    ],
                    'cities',
                    'countries' => [
                        'RS', 'ME'
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
                        'BEG', 'TIV', 'LGW', 'LHR'
                    ],
                    'cities',
                    'countries' => [
                        'RS', 'ME', 'GB'
                    ],
                    'aircrafts'
                ],
                'system'
            ], null)
            ->assertJsonCount(2, 'flights.search.request.segments')
            ->assertJsonCount(2, 'flights.search.request.passengers')
            ->assertJsonCount(3, 'guide.cities')
            ->assertJsonCount(4, 'guide.airports')
            ->assertJsonCount(3, 'guide.countries')
            ->assertJsonCount(3, 'guide.aircrafts')
            ->assertJsonCount(12, 'flights.search.results.flightGroups')
            ->assertJsonCount(2, 'flights.search.results.flightGroups.0.segments')
            ->assertJsonCount(2, 'flights.search.results.flightGroups.1.segments')
            ->assertJsonCount(3, 'flights.search.results.flightGroups.4.segments')
            ->assertJsonCount(3, 'flights.search.results.flightGroups.5.segments')
            ->assertJsonCount(3, 'flights.search.results.flightGroups.11.segments')
            ->assertJsonCount(8, 'flights.search.results.groupsData.segments')
            ->assertJsonCount(4, 'flights.search.results.groupsData.prices')
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.P1.passengerFares')
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.flightPrice.amount', 41823)
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.totalPrice.amount', 41823 + $this->agencyChargeAmount * $countOfPassenger)
            ->assertJsonPath('flights.search.results.groupsData.prices.P4.flightPrice.amount', 279780)
            ->assertJsonPath('flights.search.results.groupsData.prices.P4.totalPrice.amount', 279780 + $this->agencyChargeAmount * $countOfPassenger)
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.P2.passengerFares')
            ->assertJsonCount(4, 'flights.search.results.groupsData.prices.P4.segmentInfo');

        $this->assertDatabaseHas('flights_search_requests', ['id' => $search['request']['id'], 'transaction_id' => $transaction_id]);
        $this->assertDatabaseHas('flights_search_results', ['request_id' => $search['request']['id'], 'price' => 'P1', 'segments' => '["S1","S3"]']);
        $this->assertDatabaseHas('flights_search_results', ['request_id' => $search['request']['id'], 'price' => 'P1', 'segments' => '["S1","S4"]']);
        $this->assertDatabaseHas('flights_search_results', ['request_id' => $search['request']['id'], 'price' => 'P2', 'segments' => '["S5","S7","S8"]']);
        $this->assertDatabaseHas('flights_search_results', ['request_id' => $search['request']['id'], 'price' => 'P3', 'segments' => '["S2","S4"]']);
        $this->assertDatabaseHas('flights_search_results', ['request_id' => $search['request']['id'], 'price' => 'P4', 'segments' => '["S5","S7","S8"]']);
        $this->assertDatabaseHas('flights_search_results', ['request_id' => $search['request']['id'], 'price' => 'P4', 'segments' => '["S6","S7","S8"]']);

        $this->assertEquals(12, FlightsSearchResult::all()->count());

        $this->json('GET', $search['results']['url'])
            ->assertStatus(200);
    }

    public function testFlightsSearchInvalidSearchId()
    {
        $this->json('GET', '/api/flights/search/results/700')
            ->assertStatus(200)
            ->assertJsonPath('flights.search.results.info.errorCode', 410)
            ->assertJsonPath('flights.search.results.info.errorMessageEng', 'Invalid SearchId');
    }
}
