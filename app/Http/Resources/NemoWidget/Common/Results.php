<?php


namespace App\Http\Resources\NemoWidget\Common;


use App\Http\Middleware\NemoWidgetCache;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class Results extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->resource->get('request')->getRequestId(),
            'url' => URL::route(NemoWidgetCache::FLIGHTS_SEARCH_POST_RESULTS, ['id' => $this->resource->get('request')->getRequestId()], false),
            $this->mergeWhen($this->resource->has('results'), [
                'flightGroups' => [],
                'groupsData' => $this->resource->get('results')->get('groupsData'),
            ])
        ];

    }
}