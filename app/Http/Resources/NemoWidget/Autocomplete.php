<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\AirportList;
use App\Http\Resources\NemoWidget\Common\City;
use App\Http\Resources\NemoWidget\Common\Country;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\NemoWidget\Common\Autocomplete as AutocompleteCommon;

class Autocomplete extends JsonResource
{

    public static $wrap;

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
                $airports = $airports->merge(new AirportList($item->nameable->city->airports));
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
            'system' => new System([])
        ];
    }

    public function withResponse($request, $response)
    {
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

}