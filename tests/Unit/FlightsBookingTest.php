<?php

namespace Tests\Unit;

use App\Adapters\FtObjectAdapter;
use App\Facades\TP;
use App\Models\FlightsSearchFlightInfo;
use App\Models\FlightsSearchRequest;
use App\Models\FlightsSearchResult;
use Tests\TestCase;

class FlightsBookingTestTest extends TestCase
{
    protected $agencyChargeAmount = FtObjectAdapter::AGENCY_CHARGE_AMOUNT;
    protected $agencyChargeCurrency = FtObjectAdapter::AGENCY_CHARGE_CURRENCY;

    public function setUp(): void
    {
        parent::setUp();
        $this->useTable('flights_search_requests');
        $this->useTable('flights_search_results');
        $this->useTable('flights_search_flight_infos');
    }


    public function testFlightsSearchFlightInfo()
    {
        $request = \factory(FlightsSearchRequest::class, 1)->create([
            'data' => json_decode(file_get_contents(__DIR__ . '/files/FlightInfo/LFS-req.json'))
        ])->first();

        $result = \factory(FlightsSearchResult::class, 1)->create(['flight_search_request_id' => $request->id])->first();

        TP::shouldReceive('AirPriceReq')
            ->once()
            ->andReturn(unserialize(file_get_contents(__DIR__ . '/files/FlightInfo/AP-rsp.obj')));

        $this->mock(\FilippoToso\Travelport\TravelportLogger::class, function ($mock) {
            $mock->shouldReceive('getLog')->andReturn(file_get_contents(__DIR__ . '/files/FlightInfo/LFS-rsp.obj'))->once();
        });

        $this->json('GET', '/api/flights/search/flightInfo/' . $request->id)
            ->assertStatus(200)
            ->assertJsonPath('flights.search.flightInfo.priceStatus.changed', false)
            ->assertJsonPath('flights.search.flightInfo.priceStatus.oldValue.amount', 572999)
            ->assertJsonPath('flights.search.flightInfo.priceStatus.oldValue.currency', 'RSD')
            ->assertJsonPath('flights.search.flightInfo.priceStatus.newValue.amount', 572999)
            ->assertJsonPath('flights.search.flightInfo.priceStatus.newValue.currency', 'RSD')
            ->assertJsonPath('flights.search.flightInfo.createOrderLink', '/checkout?id=1');


        $this->assertDatabaseHas('flights_search_flight_infos', ['flight_search_result_id' => $result->id, 'transaction_id' => '4DD22E310A076478E8B1D2309D8229A7']);
    }

    public function testCheckout()
    {
        /** does not matter what includes this table */
        $request = \factory(FlightsSearchRequest::class, 1)->create([
            'data' => ['dummy']
        ])->first();

        /** does not matter what includes this table */
        $result = \factory(FlightsSearchResult::class, 1)->create(['flight_search_request_id' => $request->id])->first();

        $flightInfo = \factory(FlightsSearchFlightInfo::class, 1)->create(['flight_search_result_id' => $result->id])->first();

        $this->mock(\FilippoToso\Travelport\TravelportLogger::class, function ($mock) {
            $mock->shouldReceive('getLog')->andReturn(file_get_contents(__DIR__ . '/files/FlightInfo/AP-rsp-2.obj'))->once();
        });
        $this->json('GET', '/api/checkout/' . $flightInfo->id)
            ->assertStatus(200)
            ->assertJsonStructure([
                'flights' => [
                    'search' => [
                        'formData', 'request', 'results', 'resultData'
                    ],
                ],
                'guide' => [
                    'airports' => [
                        'BEG', 'AMS', 'CDG', 'SIN'
                    ],
                    'cities',
                    'countries' => [
                        'RS', 'NL', 'FR', 'SG'
                    ],
                    'airlines' => [
                        'JU', 'KL',
                    ]
                ],
                'system'
            ], null)
            ->assertJsonPath('flights.search.request.segments', null) // because request data id dummy
            ->assertJsonPath('flights.search.request.passengers', null) // because request data id dummy
            ->assertJsonCount(5, 'flights.search.results.groupsData.segments')
            ->assertJsonCount(1, 'flights.search.results.groupsData.prices')
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.0.airSolution')
            ->assertJsonCount(3, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo')
            ->assertJsonCount(3, 'flights.search.results.groupsData.prices.0.airSolution.1.airPricingInfo')
            ->assertJsonCount(12, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.taxes')
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.fareInfo')
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.fareInfo.0.brand.title')
            ->assertJsonCount(4, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.fareInfo.0.brand.text')
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.fareInfo.0.brand.imageLocation')
            ->assertJsonCount(45, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.fareInfo.0.brand.optimalService')
            ->assertJsonCount(1, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.fareInfo.0.fareSurcharge')
            ->assertJsonCount(5, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.bookingInfo')
            ->assertJsonCount(1, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.chargePenalty')
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.chargePenalty.0.price.amount', 17692)
            ->assertJsonCount(1, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.cancelPenalty')
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.totalFare.amount', 161488)
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.baggageAllowances.baggageAllowanceInfo')
            ->assertJsonCount(5, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.baggageAllowances.carryOnAllowanceInfo')
            ->assertJsonCount(1, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.baggageAllowances.carryOnAllowanceInfo.0.carryOnDetails')
            ->assertJsonCount(2, 'flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.baggageAllowances.baggageAllowanceInfo.0.baggageDetail')
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.baggageAllowances.baggageAllowanceInfo.0.baggageDetail.0.basePrice.amount', 7667)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.baggageAllowances.baggageAllowanceInfo.0.baggageDetail.0.approximateBasePrice.amount', 7667)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.baggageAllowances.baggageAllowanceInfo.0.baggageDetail.0.totalPrice.amount', 7667)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.baggageAllowances.baggageAllowanceInfo.0.baggageDetail.0.approximateTotalPrice.amount', 7667)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.airPricingInfo.0.baggageAllowances.baggageAllowanceInfo.0.baggageDetail.1.approximateTotalPrice.amount', 11796)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.agencyCharge.amount', 1980)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.totalPrice.amount', 497650)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.paymentOptionCharge.cache.amount', 3180)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.paymentOptionCharge.intesa.amount', 44788.5)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.paymentOptionCharge.paypal.amount', 14461.85)
            ->assertJsonCount(21, 'flights.search.results.groupsData.prices.0.airSolution.0.fareNote')
            ->assertJsonCount(6, 'flights.search.results.groupsData.prices.0.airSolution.0.hostToken')
            ->assertJsonCount(12, 'flights.search.results.groupsData.prices.0.fareRule')
            ->assertJsonCount(15, 'flights.search.results.groupsData.prices.0.fareRule.0.fareRuleLong');
    }


}
