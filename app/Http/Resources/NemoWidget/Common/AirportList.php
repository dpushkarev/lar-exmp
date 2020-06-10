<?php

namespace App\Http\Resources\NemoWidget\Common;

use App\Models\City;
use Illuminate\Http\Resources\Json\JsonResource;

class AirportList extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $airportList = collect();
        foreach ($this->resource->city->airports as $airport) {
            $airportList->put($airport->code, new Airport($airport));
        }

        if($this->resource instanceof City) {
            $airportList->put($this->resource->code, new Airport($this->resource));
        }

        return $airportList;
    }

}