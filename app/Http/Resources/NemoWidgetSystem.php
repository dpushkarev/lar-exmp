<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

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
                        "currentLanguage" => "en",
                        "currentCurrency" => "RUB",
                        "agencyCurrency" => "RUB",
                        "agencyCountry" => "RU",
                        "googleMapsApiKey" => "AIzaSyB-8D4iRGP1qgLShbdbqIYm-3spSP-bA_w",
                        "googleMapsClientId" => "",
                        "showFullFlightsResults" => "false"
                    ]
                ]
            ]
        ];
    }
}