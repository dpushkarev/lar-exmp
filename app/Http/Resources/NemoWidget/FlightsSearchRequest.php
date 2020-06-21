<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\AirportList;
use App\Http\Resources\NemoWidget\Common\City;
use App\Http\Resources\NemoWidget\Common\Country;
use App\Http\Resources\NemoWidget\Common\Flights;
use App\Http\Resources\NemoWidget\Common\FormData;
use App\Http\Resources\NemoWidget\Common\Request;
use App\Http\Resources\NemoWidget\Common\Results;
use App\Models\City as CityModel;
use App\Models\Airport as AirportModel;

class FlightsSearchRequest extends AbstractResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'flights' => [
                'search' => [
                    'formData' => new FormData($this->resource->get('request')->id),
                    'request' => new Request($this->resource->get('request')),
                    'results' => new Results(collect(['request_id' => $this->resource->get('request')->id]))
                ]
            ],
            'guide' => [
                'airports' => new AirportList($this->resource->get('airports')),
                'cities' => $this->resource->get('cities'),
                'countries' => $this->resource->get('countries')
            ],
        ];
    }

    public function withResponse($request, $response)
    {
        parent::withResponse($request, $response);
        $response->requestId = $this->resource->get('request')->id;
    }

}