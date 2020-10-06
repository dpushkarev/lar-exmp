<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Resources\Json\JsonResource;

class Confirment extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'number_invoice' => $this->resource->id,
            'passengers' => $this->resource->data['passengers'],
            'address' => $this->resource->data['address'],
            'phoneNumber' => $this->resource->data['phoneNumber'],
            'paymentOption' => $this->resource->data['paymentOption'],
            'email' => $this->resource->data['email'],
        ];
    }
}