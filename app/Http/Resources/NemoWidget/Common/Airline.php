<?php


namespace App\Http\Resources\NemoWidget\Common;


use Illuminate\Http\Resources\Json\JsonResource;

class Airline extends JsonResource
{
    public function toArray($request)
    {
        return [
            $this->resource->code ?? "" => [
                "IATA" => $this->resource->code ?? null,
                "name" => __($this->resource->name ?? null),
                "nameEn" => $this->resource->name ?? null,
                "nameFallback" => $this->resource->short_name ?? null,
                "rating" => $this->resource->rating ?? null,
                "countryCode" => $this->resource->country_code ?? null,
                'logo' => $this->resource->logo ?? null,
                'monochromeLogo' => $this->resource->monochromeLogo ?? null,
                'colors' => $this->resource->colors ?? null
            ]
        ];
    }
}