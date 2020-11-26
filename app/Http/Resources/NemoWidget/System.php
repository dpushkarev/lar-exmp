<?php

namespace App\Http\Resources\NemoWidget;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class System extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'info' => [
                "response" => [
                    "timestamp" => time(),
                    "responseTime" => microtime(true) - LARAVEL_START
                ],
                "user" => [
                    "userID" => null,
                    "agencyID" => null,
                    "status" => "guest",
                    "isB2B" => false,
                    "settings" => [
                        "currentLanguage" => App::getLocale(),
                        "currentCurrency" => 'RSD',
                        "agencyCurrency" => 'RSD',
                        "agencyCountry" => 'RS',
                        "googleMapsApiKey" => null,
                        "googleMapsClientId" => null,
                        "googleRecaptchaSiteKey" => '6LdAWfQUAAAAAIYESdG7Q2bbBIiFCaStKKqGF1-Y',
                        "showFullFlightsResults" => "false"
                    ]
                ]
            ]
        ];
    }
}