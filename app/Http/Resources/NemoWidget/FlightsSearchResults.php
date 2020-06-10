<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\AirlineList;
use App\Http\Resources\NemoWidget\Common\AirportList;
use App\Http\Resources\NemoWidget\Common\FormData;
use App\Http\Resources\NemoWidget\Common\Request;
use App\Http\Resources\NemoWidget\Common\ResultData;
use App\Http\Resources\NemoWidget\Common\Results;

class FlightsSearchResults extends AbstractResource
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
                    'formData' => new FormData($this->resource->get('request')),
                    'request' => new Request($this->resource->get('request')),
                    'results' => new Results($this->resource->get('results')->put('request', $this->resource->get('request'))),
                    'resultData' => new ResultData([])
                ]
            ],
            'guide' => [
                'airlines' => new AirlineList($this->resource->get('airlines')),
                'airports' => new AirportList($this->resource->get('airports')),
                'cities' => $this->resource->get('cities'),
                'countries' => $this->resource->get('countries')
            ],
        ];
    }

}
