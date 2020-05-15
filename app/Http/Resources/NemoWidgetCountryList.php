<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NemoWidgetCountryList extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $countryList = collect();
        foreach ($this->resource as $country) {
            $countryList = $countryList->merge(new NemoWidgetCountry($country));
        }

        return $countryList;


    }
}