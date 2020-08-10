<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\FormData;
use App\Http\Resources\NemoWidget\Common\Guide;
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
                    'formData' => new FormData($this->resource->get('request')->id),
                    'request' => new Request($this->resource->get('request')),
                    'results' => new Results(collect(['results' => $this->resource->get('results'), 'request_id' => $this->resource->get('request')->id])),
                    'resultData' => new ResultData([])
                ]
            ],
            $this->merge(new Guide($this->resource))
        ];
    }

}
