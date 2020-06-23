<?php


namespace App\Http\Resources\NemoWidget\Common;


use App\Http\Middleware\NemoWidgetCache;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class Results extends JsonResource
{
    public function toArray($request)
    {
        /** @var  $results Collection */

        $results = collect([]);
        if($this->resource->has('results')) {
            $results = $this->resource->get('results');
        }

        return [
            $this->mergeWhen(($this->resource->has('request_id')), [
                'id' => $this->resource->get('request_id'),
                'url' => URL::route(NemoWidgetCache::FLIGHTS_SEARCH_POST_RESULTS, ['id' => $this->resource->get('request_id', 0)], false),
            ]),
            $this->mergeWhen($results->isNotEmpty(), [
                'flightGroups' => FlightGroups::collection($results->get('flightGroups', [])),
                'groupsData' => $results->get('groupsData', []),
                'info' => $results->get('info', [])
            ]),
        ];

    }
}