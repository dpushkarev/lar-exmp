<?php

namespace App\Http\Resources\NemoWidget\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class City extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "IATA" => $this->resource->code,
            "name" => __($this->resource->name),
            "nameEn" => $this->resource->name,
            "countryCode" => $this->resource->country_code,
            "id" => $this->resource->id,
            'airports' => AirportIata::collection($this->resource->airports)
        ];
    }

}