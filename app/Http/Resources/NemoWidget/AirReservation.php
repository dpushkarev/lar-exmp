<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\Guide;

class AirReservation extends AbstractResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'universalRecord' => $this->resource->get('universalRecord'),
            'responseTime' => $this->resource->get('responseTime'),
            $this->merge(new Guide($this->resource))
        ];
    }

}
