<?php

namespace Tests\Unit;

use App\Facades\TP;
use App\Models\FlightsSearchFlightInfo;
use App\Models\FlightsSearchRequest;
use App\Models\FlightsSearchResult;
use App\Models\FrontendDomainRule;
use Tests\TestCase;

class FlightsBookingTestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->useTable('flights_search_requests');
        $this->useTable('flights_search_results');
        $this->useTable('flights_search_flight_infos');
        $this->useTable('reservations');
        $this->useTable('platform_rules');
    }


    public function testFlightsSearchFlightInfo()
    {
        $request = \factory(FlightsSearchRequest::class, 1)->create([
            'data' => json_decode(file_get_contents(__DIR__ . '/files/FlightInfo/LFS-req.json'))
        ])->first();

        $rule = factory(FrontendDomainRule::class, 1)->create(['platform_id' => 1])->first();
        $result = \factory(FlightsSearchResult::class, 1)->create(['flight_search_request_id' => $request->id, 'rule_id' => $rule->id])->first();

        TP::shouldReceive('AirPriceReq')
            ->once()
            ->andReturn(unserialize(file_get_contents(__DIR__ . '/files/FlightInfo/AP-rsp.obj')));

        $this->mock(\FilippoToso\Travelport\TravelportLogger::class, function ($mock) {
            $mock->shouldReceive('getLog')->andReturn(file_get_contents(__DIR__ . '/files/FlightInfo/LFS-rsp.obj'))->once();
        });

        $response = $this->json('GET', '/api/flights/search/flightInfo/' . $request->id)
            ->assertStatus(200)
            ->assertJsonPath('flights.search.flightInfo.priceStatus.changed', false)
            ->assertJsonPath('flights.search.flightInfo.priceStatus.oldValue.amount', 573002)
            ->assertJsonPath('flights.search.flightInfo.priceStatus.oldValue.currency', 'RSD')
            ->assertJsonPath('flights.search.flightInfo.priceStatus.newValue.amount', 573002)
            ->assertJsonPath('flights.search.flightInfo.priceStatus.newValue.currency', 'RSD')
            ->decodeResponseJson();

        $this->assertRegExp('/\/reservation\/[0-9a-f]{10}/', $response['flights']['search']['flightInfo']['createOrderLink']);

        $this->assertDatabaseHas('flights_search_flight_infos', ['flight_search_result_id' => $result->id, 'transaction_id' => '4DD22E310A076478E8B1D2309D8229A7']);
    }

    public function testCheckout()
    {
        $request = \factory(FlightsSearchRequest::class, 1)->create([
            'data' => json_decode(file_get_contents(__DIR__ . '/files/FlightInfo/LFS-req-2.json'))
        ])->first();

        $rule = factory(FrontendDomainRule::class, 1)->create(['platform_id' => 1])->first();

        $result = \factory(FlightsSearchResult::class, 1)->create(['flight_search_request_id' => $request->id, 'rule_id' => $rule->id])->first();

        $flightInfo = \factory(FlightsSearchFlightInfo::class, 1)->create(['flight_search_result_id' => $result->id])->first();

        $this->mock(\FilippoToso\Travelport\TravelportLogger::class, function ($mock) {
            $mock->shouldReceive('getLog')->andReturn(file_get_contents(__DIR__ . '/files/FlightInfo/LFS-rsp.obj'))->once();
            $mock->shouldReceive('getLog')->andReturn(file_get_contents(__DIR__ . '/files/FlightInfo/AP-rsp-2.obj'))->twice();
        });

        /** Get checkout */
        $this->json('GET', '/api/checkout/' . $flightInfo->code)
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
            ->assertJsonCount(2, 'flights.search.request.segments')
            ->assertJsonCount(3, 'flights.search.request.passengers')
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
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.agencyCharge.amount', 6)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.totalPrice.amount', 495676)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.paymentOptionCharge.cash.amount', 1)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.paymentOptionCharge.intesa.amount', 1)
            ->assertJsonPath('flights.search.results.groupsData.prices.0.airSolution.0.paymentOptionCharge.paypal.amount', 0)
            ->assertJsonCount(21, 'flights.search.results.groupsData.prices.0.airSolution.0.fareNote')
            ->assertJsonCount(6, 'flights.search.results.groupsData.prices.0.airSolution.0.hostToken')
            ->assertJsonCount(12, 'flights.search.results.groupsData.prices.0.fareRule')
            ->assertJsonCount(15, 'flights.search.results.groupsData.prices.0.fareRule.0.fareRuleLong')
            ->assertJsonCount(3, 'flights.search.results.info');

        TP::shouldReceive('AirCreateReservationReq')
            ->once()
            ->andReturn(unserialize(file_get_contents(__DIR__ . '/files/Reservation/ACR-rsp.obj')));

        /** Reservation */
        $response = $this->json('POST','/api/reservation/' . $flightInfo->code, ['request' => file_get_contents(__DIR__ . '/files/Reservation/reservation.json')])
            ->assertStatus(200)
            ->assertJsonCount(5, 'guide')
            ->assertJsonCount(4, 'universalRecord.bookingTraveler')
            ->assertJsonPath('universalRecord.bookingTraveler.0.bookingTravelerName.first', 'Pushakrev')
            ->assertJsonCount(2, 'universalRecord.bookingTraveler.0.phoneNumber')
            ->assertJsonCount(1, 'universalRecord.bookingTraveler.0.email')
            ->assertJsonCount(1, 'universalRecord.bookingTraveler.0.address')
            ->assertJsonCount(1, 'universalRecord.actionStatus')
            ->assertJsonPath('universalRecord.actionStatus.0.type', 'ACTIVE')
            ->assertJsonCount(1, 'universalRecord.providerReservationInfo')
            ->assertJsonCount(2, 'universalRecord.airReservation.supplierLocator')
            ->assertJsonCount(4, 'universalRecord.airReservation.bookingTravelerRef')
            ->assertJsonCount(1, 'universalRecord.airReservation.airSegmentInfo.0.flightDetails')
            ->assertJsonCount(6, 'universalRecord.airReservation.airSegmentInfo')
            ->assertJsonPath('universalRecord.paymentOptionCharge.cash.amount', 1)
            ->assertJsonPath('universalRecord.paymentOptionCharge.intesa.amount', 1)
            ->assertJsonPath('universalRecord.agencyInfo.agentAction.0.actionType', 'Created')
            ->assertJsonPath('universalRecord.locatorCode', '8EHXP0')
            ->assertJsonPath('universalRecord.status', 'Active')
            ->assertJsonStructure([
                'universalRecord' => [
                    'formOfPayment' => [
                        [
                            'check', 'providerReservationInfoRef'
                        ]
                    ]
                ]
            ])
            ->decodeResponseJson();

        $this->assertNotNull($response['reservationCode']);
        $this->assertNotNull($response['reservationAccessCode']);

        $this->assertDatabaseHas('reservations', ['flights_search_flight_info_id' => $flightInfo->id, 'transaction_id' => '0C42A5590A0742610CA0F41AE03B3BD2']);

        $this->json('POST','/api/reservation/' . $flightInfo->code, ['request' => file_get_contents(__DIR__ . '/files/Reservation/reservation.json')])
            ->assertStatus(200)
            ->assertJsonPath('message', "Finished checkout")
            ->assertJsonPath('reservationCode', $response['reservationCode']);

        $this->mock(\FilippoToso\Travelport\TravelportLogger::class, function ($mock) {
            $mock->shouldReceive('getLog')->andReturn(file_get_contents(__DIR__ . '/files/Reservation/ACR-rsp.obj'))->once();
        });

//        $this->json('GET','/api/reservation/' . $response['reservationCode'], ['access_code' => 'dfs43jkjk'])
//            ->assertStatus(422);

        $this->json('GET','/api/reservation/' . $response['reservationCode'], ['access_code' => $response['reservationAccessCode']])
            ->assertStatus(200)
            ->assertJsonPath('paymentOption', 'card')
            ->assertJsonStructure([
                'universalRecord' => [
                    'formOfPayment' => [
                        [
                            'check', 'providerReservationInfoRef'
                        ]
                    ]
                ],
            ]);

    }

}
