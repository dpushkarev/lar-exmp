<?php

namespace App\Http\Resources\NemoWidget\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryList extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $countryList = collect();
        foreach ($this->resource as $country) {
            $countryList = $countryList->merge(new Country($country));
        }

        return $countryList;


    }
}