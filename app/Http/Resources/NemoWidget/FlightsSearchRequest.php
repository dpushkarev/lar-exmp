<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\Flights;
use App\Http\Resources\NemoWidget\Common\FormData;
use App\Http\Resources\NemoWidget\Common\Guide;
use App\Http\Resources\NemoWidget\Common\Request;
use App\Http\Resources\NemoWidget\Common\Results;

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
            $this->merge(new Guide($this->resource))
        ];
    }

    public function withResponse($request, $response)
    {
        parent::withResponse($request, $response);
        $response->requestId = $this->resource->get('request')->id;
    }

}