<?php


namespace App\Http\Resources\NemoWidget\Common;


use Illuminate\Http\Resources\Json\JsonResource;

class Aircraft extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'capacity' => null,
            'cruiseSpeed' => null,
            'distanceType' => null,
            'fuselageType' => null,
            'image' => null,
            'isHomeAirctaft' => false,
            'isTurbineAirctaft' => false,
            'manufacture' => '-',
            'map_image' => null,
            "code" => $this->resource->code,
            "name" => __($this->resource->name),
            "nameEn" => $this->resource->name,
            'originCountries' => null
        ];
    }
}