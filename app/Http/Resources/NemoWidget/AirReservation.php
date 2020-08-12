<?php

namespace App\Http\Resources\NemoWidget;

class AirReservation extends AbstractResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource;
    }

}
