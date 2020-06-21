<?php


namespace App\Http\Resources\NemoWidget\Common;


use Illuminate\Http\Resources\Json\JsonResource;

class Aircraft extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'capacity' => '?',
            'cruiseSpeed' => '?',
            'distanceType' => '?',
            'fuselageType' => '?',
            'image' => '?',
            'isHomeAirctaft' => '?',
            'isTurbineAirctaft' => '?',
            'manufacture' => '?',
            'map_image' => '?',
            "code" => $this->resource->code,
            "name" => __($this->resource->name),
            "nameEn" => $this->resource->name,
            'originCountries' => '?'
        ];
    }
}