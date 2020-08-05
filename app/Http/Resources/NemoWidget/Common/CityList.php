<?php

namespace App\Http\Resources\NemoWidget\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class CityList extends JsonResource
{
    public $preserveKeys = true;

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $cityList = collect();
        foreach ($this->resource as $city) {
           $cityList->put($city->id, new City($city));
        }

        return $cityList;
    }
}