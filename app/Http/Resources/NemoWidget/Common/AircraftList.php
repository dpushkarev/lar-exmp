<?php

namespace App\Http\Resources\NemoWidget\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class AircraftList extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $aircraftList = collect();
        foreach ($this->resource as $aircraft) {
            $aircraftList->put($aircraft->code, new Aircraft($aircraft));
//            $aircraftList["{$aircraft->code}"] = new Aircraft($aircraft);
        }

        return $aircraftList;
    }
}