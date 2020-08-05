<?php


namespace App\Http\Resources\NemoWidget\Common;


use App\Http\Resources\NemoWidget\AbstractResource;
use App\Http\Resources\NemoWidget\Common\Autocomplete as AutocompleteCommon;

class Guide extends AbstractResource
{
    public function toArray($request)
    {
        return [
            'guide' => [
                $this->mergeWhen($this->resource->has('autocomplete'), [
                    'autocomplete' => [
                        'iata' => AutocompleteCommon::collection($this->resource->get('autocomplete'))
                    ]
                ]),
                $this->mergeWhen($this->resource->has('airlines'), [
                    'airlines' => new AirlineList($this->resource->get('airlines')),
                ]),
                $this->mergeWhen($this->resource->has('aircrafts'), [
                    'aircrafts' => new AircraftList($this->resource->get('aircrafts')),
                ]),
                $this->mergeWhen($this->resource->has('airports'), [
                    'airports' => new AirportList($this->resource->get('airports')),
                ]),
                $this->mergeWhen($this->resource->has('cities'), [
                    'cities' => new CityList($this->resource->get('cities')),
                ]),
                $this->mergeWhen($this->resource->has('countries'), [
                    'countries' => new CountryList($this->resource->get('countries')),
                ]),
            ]
        ];
    }
}