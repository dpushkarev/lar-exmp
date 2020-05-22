<?php

namespace App\Http\Resources\NemoWidget\Common;

use App\Models\City;
use Illuminate\Http\Resources\Json\JsonResource;

class Autocomplete extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "IATA" => $this->resource->nameable->code,
            "isCity" => ($this->resource->nameable instanceof City),
            "cityId" => $this->resource->nameable->city->id
        ];
    }
}