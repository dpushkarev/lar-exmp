<?php

namespace App\Http\Resources\NemoWidget\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class AirlineList extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $airlineList = collect();
        foreach ($this->resource as $airline) {
            $airlineList = $airlineList->merge(new Airline($airline));
        }

        return $airlineList;
    }
}