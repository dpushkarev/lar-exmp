<?php


namespace App\Http\Resources\NemoWidget\Common;


use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class Results extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->resource->get('request')->getRequestId(),
            'url' => URL::route('flights.search.results', ['id' => $this->resource->get('request')->getRequestId()], false),
            $this->mergeWhen($this->resource->has('results'), [
                'flightGroups' => $this->resource->get('results'),
                'groupsData' => $this->resource->get('results'),
            ])
        ];

    }
}