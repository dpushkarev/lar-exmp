<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\AirportList;
use App\Http\Resources\NemoWidget\Common\City;
use App\Http\Resources\NemoWidget\Common\Country;
use App\Http\Resources\NemoWidget\Common\Autocomplete as AutocompleteCommon;

class Autocomplete extends AbstractResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $countries = collect();
        $cities = collect();
        $airports = collect();
        $iata = collect();

        if (null !== $this->resource) {
            foreach ($this->resource as $item) {
                $countries = $countries->merge(new Country($item->nameable->country));
                $cities[$item->nameable->city->id] = new City($item->nameable->city);
                $airports = $airports->merge(new AirportList($item->nameable));
            }

            $iata = AutocompleteCommon::collection($this->resource);
        }

        return [
            'guide' => [
                'autocomplete' => [
                    'iata' => $iata,
                ],
                $this->mergeWhen($countries->isNotEmpty(), [
                    'countries' => $countries,
                ]),
                'cities' => $cities,
                $this->mergeWhen($airports->isNotEmpty(), [
                    'airports' => $airports
                ]),
            ],
        ];
    }

}