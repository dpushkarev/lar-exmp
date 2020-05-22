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
                "rating" => null,
                "countryCode" => $this->resource->country_code,
                $this->mergeWhen($this->resource->logo, [
                    'logo' => [
                        'image' => $this->resource->logo,
                        'icon' => $this->resource->logo,
                        'width' => $this->resource->width,
                        'height' => $this->resource->height,
                    ]
                ]),
                'monochromeLogo' => null,
                'colors' => [
                    'companyColor' => null,
                    'companyColorAdditional' => null
                ]
            ]
        ];
    }
}