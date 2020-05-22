<?php

namespace App\Http\Resources\NemoWidget\Common;

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
        foreach ($this->resource as $airport) {
            $airportList->put($airport->code, new Airport($airport));
        }

        return $airportList;
    }

}