<?php


namespace App\Http\Resources\NemoWidget\Common;


use App\Http\Middleware\NemoWidgetCache;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class FormData extends JsonResource
{
    public function toArray($request)
    {
        return [
            "maxLimits" => [
                "passengerCount" => [
                    "ADT" => "6",
                    "SRC" => "6",
                    "YTH" => "6",
                    "CLD" => "4",
                    "INF" => "2",
                    "INS" => "2"
                ],
                "totalPassengers" => "9",
                "flightSegments" => "5"
            ],
            "dateOptions" => [
                "minOffset" => 2,
                "maxOffset" => 365,
                "aroundDatesValues" => [
                    1,
                    2,
                    3
                ]
            ],
            "showCitySwapBtn" => true,
            "scheduleSearchEnable" => false,
            "onFocusAutocomplete" => false,
            "forceAggregationAirports" => false,
            "searchWithoutAdults" => false,
            "hideDirectOnlyCheckbox" => false,
            "highlightDates" => false,
            "disableUnavailableDate" => false,
            "passengersSelect" => [
                "extendedPassengersSelect" => false,
                "passengersSelectAlt" => true,
                "tripType" => "select",
                "fastPassengersSelect" => [
                    [
                        "label" => "singleAdult",
                        "set" => [
                            "ADT" => 1
                        ]
                    ],
                    [
                        "label" => "twoAdults",
                        "set" => [
                            "ADT" => 2
                        ]
                    ],
                    [
                        "label" => "twoAdultsWithChild",
                        "set" => [
                            "ADT" => 2,
                            "CLD" => 1
                        ]
                    ]
                ]
            ],
            "id" => $this->resource,
            "url" =>  URL::route(NemoWidgetCache::FLIGHTS_SEARCH_GET_FORM_DATA, ['id' => $this->resource], false)
        ];
    }
}