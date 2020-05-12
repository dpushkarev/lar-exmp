<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class NemoWidgetSystem extends JsonResource
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
                        "currentCurrency" => null,
                        "agencyCurrency" => null,
                        "agencyCountry" => null,
                        "googleMapsApiKey" => null,
                        "googleMapsClientId" => null,
                        "showFullFlightsResults" => "false"
                    ]
                ]
            ]
        ];
    }
}