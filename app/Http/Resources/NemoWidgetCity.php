<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NemoWidgetCity extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            $this->resource->code => [
                "IATA" => $this->resource->code,
                "name" => __($this->resource->name),
                "nameEn" => $this->resource->name,
                "countryCode" => $this->resource->country_code,
                "id" => $this->resource->id,
                'airports' => NemoWidgetAirportIata::collection($this->resource->airports)
            ]
        ];
    }
}