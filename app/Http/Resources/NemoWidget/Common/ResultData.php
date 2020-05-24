<?php


namespace App\Http\Resources\NemoWidget\Common;


use Illuminate\Http\Resources\Json\JsonResource;

class ResultData extends JsonResource
{
    public function toArray($request)
    {
        return [
            "postFilters" => [
                "postFiltersSort" => [
                    "flightID",
                    "freeBaggage",
                    "timeEnRoute",
                    "departureAirport",
                    "arrivalAirport",
                    "departureTime",
                    "arrivalTime",
                    "transfersCount",
                    "price",
                    "carrier",
                    "transfersDuration",
                    "travelPolicies"
                ],
                "showPostFilterHint" => false
            ],
            "defaultSort" => "price",
            "carrierDefaultSort" => "depTime",
            "showAirplanePopup" => true,
            "defaultViewType" => "tile",
            "showBlocks" => [
                "useShowCase" => true,
                "showBestOffers" => true,
                "useFlightCompareTable" => true,
                "hideViewTypeButtons" => false,
                "allowСlaimСreation" => false,
                "showFareVariations" => false
            ],
            "searchTimeout" => [
                "useSearchTimeout" => false
            ],
            "needCheckAvail" => false,
            "compareTableTransfersType" => "sum"
        ];
    }
}