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
            'paymentOption' => $this->resource->get('paymentOption'),
            'responseTime' => $this->resource->get('responseTime'),
            'passengersCount' => $this->resource->get('passengersCount'),
            'airSolutionChangeInfo' => $this->resource->get('airSolutionChangeInfo'),
            'reservationCode' => $this->resource->get('reservationCode'),
            'reservationAccessCode' => $this->resource->get('reservationAccessCode'),
            $this->merge(new Guide($this->resource))
        ];
    }

}
