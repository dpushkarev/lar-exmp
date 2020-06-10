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
        $flightGroups = $this->resource->has('flightGroups') ? $this->resource->get('flightGroups') : collect();
        $groupsData = $this->resource->has('groupsData') ? $this->resource->get('groupsData') : collect();

        return [
            'id' => $this->resource->get('request')->getRequestId(),
            'url' => URL::route(NemoWidgetCache::FLIGHTS_SEARCH_POST_RESULTS, ['id' => $this->resource->get('request')->getRequestId()], false),
            $this->mergeWhen($flightGroups->isNotEmpty(), [
                'flightGroups' => FlightGroups::collection($flightGroups),
            ]),
            $this->mergeWhen($groupsData->isNotEmpty(), [
                'groupsData' => $groupsData,
            ])
        ];

    }
}