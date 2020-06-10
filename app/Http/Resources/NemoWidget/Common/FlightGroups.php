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
                    'canProcessFareFamilies' => '?',
                    'createOrderLink' => "?",
                    'expectedNumberOfTickets' => '?',
                    'id' => $this->resource->id,
                    'key' => '?',
                    'nemo2id' => "?",
                    'price' => $this->resource->price,
                    'rating' => '?',
                    'service' => TravelPortService::APPLICATION,
                    'travelPolicies' => [],
                ]
            ],
            'segments' => $this->resource->segments
        ];
    }
}