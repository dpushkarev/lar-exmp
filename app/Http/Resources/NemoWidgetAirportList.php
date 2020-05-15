<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NemoWidgetAirportList extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $airportList = collect();
        foreach ($this->resource as $airport) {
            $airportList->put($airport->code, new NemoWidgetAirport($airport));
        }

        return $airportList;
    }

}