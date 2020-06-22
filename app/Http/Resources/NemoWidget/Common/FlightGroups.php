<?php


namespace App\Http\Resources\NemoWidget\Common;


use App\Services\TravelPortService;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightGroups extends JsonResource
{
    public function toArray($request)
    {
        return [
            'flights' => [
                [
                    'canProcessFareFamilies' => false,
                    'createOrderLink' => "/checkout/",
                    'expectedNumberOfTickets' => false,
                    'id' => $this->resource->id,
                    'nemo2id' => $this->resource->id,
                    'price' => $this->resource->price,
                    'rating' => mt_rand(900000, 1000000) / 100000,
                    'service' => TravelPortService::APPLICATION,
                    'travelPolicies' => [],
                ]
            ],
            'segments' => $this->resource->segments
        ];
    }
}