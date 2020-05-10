<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NemoWidgetCountries extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            $this->resource->code => [
                "code" => $this->resource->code,
                "name" => $this->resource->name,
                "nameEn" => $this->resource->name,
            ]
        ];
    }
}