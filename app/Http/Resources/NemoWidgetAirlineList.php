<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NemoWidgetAirlineList extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $airlineList = collect();
        foreach ($this->resource as $airline) {
            $airlineList = $airlineList->merge(new NemoWidgetAirline($airline));
        }

        return $airlineList;
    }
}