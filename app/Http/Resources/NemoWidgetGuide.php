<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NemoWidgetGuide extends JsonResource
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
                $countries = $countries->merge(new NemoWidgetCountry($item->nameable->country));
                $cities = $cities->push($item->nameable->city->id, new NemoWidgetCity($item->nameable->city));
                $airports = $airports->merge(new NemoWidgetAirportList($item->nameable->city->airports));
            }

            $iata = NemoWidgetAutocomplete::collection($this->resource);
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
            'system' => new NemoWidgetSystem(1)
        ];
    }

    public function withResponse($request, $response)
    {
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

}