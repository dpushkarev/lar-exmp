<?php

namespace Tests\Unit;

use App\Adapters\FtObjectAdapter;
use App\Facades\TP;
use App\Models\FlightsSearchRequest;
use App\Models\FlightsSearchResult;
use Tests\TestCase;

class FlightsSearchTest extends TestCase
{
    protected $agencyChargeAmount = FtObjectAdapter::AGENCY_CHARGE_AMOUNT;
    protected $agencyChargeCurrency = FtObjectAdapter::AGENCY_CHARGE_CURRENCY;

    public function setUp(): void
    {
        parent::setUp();
        $this->useTable('flights_search_requests');
        $this->useTable('flights_search_results');
        $this->useTable('flights_search_flight_infos');
        $this->useTableWithData('aircrafts');

    }

    public function testFlightsSearchDirect()
    {
        $transaction_id = 'C8B3F5700A07647789A75EC6238CACE3';
        $countOfPassenger = 3;

        TP::shouldReceive('LowFareSearchReq')
            ->once()
            ->andReturn(unserialize(file_get_contents(__DIR__ . '/files/FlightsSearch/LFS-rsp.obj')));

        $body = file_get_contents(__DIR__ . '/files/FlightsSearch/LFS-req.json');

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
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.agencyCharge.amount', $this->agencyChargeAmount * $countOfPassenger)
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.agencyCharge.currency', $this->agencyChargeCurrency)
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.flightPrice.amount', 41739)
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.totalPrice.amount', 41739 + $this->agencyChargeAmount * $countOfPassenger)
            ->assertJsonPath('flights.search.results.groupsData.prices.P2.flightPrice.amount', 58371)
            ->assertJsonPath('flights.search.results.groupsData.prices.P2.totalPrice.amount', 58371 + $this->agencyChargeAmount * $countOfPassenger)
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.P2.passengerFares');

        $this->assertDatabaseHas('flights_search_requests', ['id' => $search['request']['id'], 'transaction_id' => $transaction_id]);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P1', 'segments' => '["S1","S2"]']);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P2', 'segments' => '["S3","S2"]']);

        $this->assertEquals(2, FlightsSearchResult::all()->count());

        $this->json('GET', $search['results']['url'])
            ->assertStatus(200);
    }

    public function testFlightsSearchConnecting()
    {
        $transaction_id = 'D7B751950A076478474E55721925C6F6';
        $countOfPassenger = 3;

        TP::shouldReceive('LowFareSearchReq')
            ->once()
            ->andReturn(unserialize(file_get_contents(__DIR__ . '/files/FlightsSearch/LFS-rsp-2.obj')));

        $body = file_get_contents(__DIR__ . '/files/FlightsSearch/LFS-req-2.json');

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
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P1', 'segments' => '["S1","S3"]']);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P1', 'segments' => '["S1","S4"]']);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P2', 'segments' => '["S5","S7","S8"]']);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P3', 'segments' => '["S2","S4"]']);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P4', 'segments' => '["S5","S7","S8"]']);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P4', 'segments' => '["S6","S7","S8"]']);

        $this->assertEquals(12, FlightsSearchResult::all()->count());

        $this->json('GET', $search['results']['url'])
            ->assertStatus(200);
    }

    public function testFlightsSearchWithTariffs()
    {
        $transaction_id = '353B7CB10A076478E8B1D23045639117';
        $countOfPassenger = 1;

        TP::shouldReceive('LowFareSearchReq')
            ->once()
            ->andReturn(unserialize(file_get_contents(__DIR__ . '/files/FlightsSearch/LFS-rsp-3.obj')));

        $body = file_get_contents(__DIR__ . '/files/FlightsSearch/LFS-req-3.json');

        $search = $this->json('POST', '/api/flights/search/request', [
            'request' => $body
        ])
            ->assertStatus(200)
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
                        'BEG', 'AUH', 'SIN', 'AMS', 'CDG'
                    ],
                    'cities',
                    'countries' => [
                        'RS', 'AE', 'SG', 'NL', 'FR'
                    ],
                    'aircrafts'
                ],
                'system'
            ], null)
            ->assertJsonCount(2, 'flights.search.request.segments')
            ->assertJsonCount(1, 'flights.search.request.passengers')
            ->assertJsonCount(5, 'guide.cities')
            ->assertJsonCount(5, 'guide.airports')
            ->assertJsonCount(5, 'guide.countries')
            ->assertJsonCount(5, 'guide.aircrafts')
            ->assertJsonCount(5, 'flights.search.results.flightGroups')
            ->assertJsonCount(4, 'flights.search.results.flightGroups.0.segments')
            ->assertJsonCount(5, 'flights.search.results.flightGroups.1.segments')
            ->assertJsonCount(5, 'flights.search.results.flightGroups.2.segments')
            ->assertJsonCount(5, 'flights.search.results.flightGroups.3.segments')
            ->assertJsonCount(5, 'flights.search.results.flightGroups.4.segments')
            ->assertJsonCount(10, 'flights.search.results.groupsData.segments')
            ->assertJsonCount(3, 'flights.search.results.groupsData.prices')
            ->assertJsonCount(1, 'flights.search.results.groupsData.prices.P1.passengerFares')
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.flightPrice.amount', 107649)
            ->assertJsonPath('flights.search.results.groupsData.prices.P1.totalPrice.amount', 107649 + $this->agencyChargeAmount * $countOfPassenger)
            ->assertJsonCount(12, 'flights.search.results.groupsData.prices.P2.passengerFares.0.taxes')
            ->assertJsonCount(6, 'flights.search.results.groupsData.prices.P3.segmentInfo')
            ->assertJsonCount(4, 'flights.search.results.groupsData.prices.P1.passengerFares.0.tariffs')
            ->assertJsonCount(6, 'flights.search.results.groupsData.prices.P3.passengerFares.0.tariffs')
            ->assertJsonPath('flights.search.results.groupsData.prices.P3.passengerFares.0.tariffs.0.segNum', 4)
            ->assertJsonPath('flights.search.results.groupsData.prices.P3.passengerFares.0.tariffs.1.segNum', 5)
            ->assertJsonPath('flights.search.results.groupsData.prices.P3.passengerFares.0.tariffs.4.segNum', 8)
            ->assertJsonStructure([
                'flights' => [
                    'search' => [
                        'results' => [
                            'groupsData' => [
                                'prices' => [
                                    'P3' => [
                                        'passengerFares' => [
                                            0 => [
                                                'tariffs' => [
                                                    0 => [
                                                        'code', 'segNum', 'features' => [
                                                            'baggage', 'refunds', 'misc'
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                ],
            ], null);

        $this->assertDatabaseHas('flights_search_requests', ['id' => $search['request']['id'], 'transaction_id' => $transaction_id]);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P1', 'segments' => '["S1","S2","S3","S4"]']);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P2', 'segments' => '["S5","S6","S7","S9","S10"]']);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P2', 'segments' => '["S5","S8","S7","S9","S10"]']);
        $this->assertDatabaseHas('flights_search_results', ['flight_search_request_id' => $search['request']['id'], 'price' => 'P3', 'segments' => '["S5","S8","S7","S9","S10"]']);

        $this->assertEquals(5, FlightsSearchResult::all()->count());

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
