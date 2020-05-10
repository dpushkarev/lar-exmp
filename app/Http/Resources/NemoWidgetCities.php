<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NemoWidgetCities extends JsonResource
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
                "name" => $this->resource->name,
                "nameEn" => $this->resource->name,
                "countryCode" => $this->resource->country_code,
                "id" => $this->resource->id,
                'airports' => $this->resource->associated_airports
            ]
        ];
    }
}