<?php


namespace App\Http\Resources\NemoWidget\Common;


use Illuminate\Http\Resources\Json\JsonResource;

class Airline extends JsonResource
{
    public function toArray($request)
    {
        return [
            $this->resource->code => [
                "IATA" => $this->resource->code,
                "name" => __($this->resource->name),
                "nameEn" => $this->resource->name,
                "nameFallback" => $this->resource->short_name,
                "rating" => $this->resource->rating,
                "countryCode" => $this->resource->country_code,
                'logo' => $this->resource->logo,
                'monochromeLogo' => $this->resource->monochromeLogo,
                'colors' => $this->resource->colors
            ]
        ];
    }
}