<?php

namespace App\Http\Controllers;

use App\Http\Resources\NemoWidgetGuide;
use App\Services\NemoWidgetService;
use Illuminate\Routing\Controller as BaseController;

/**
 * Class NemoWidget
 * @package App\Http\Controllers
 */
class NemoWidget extends BaseController
{
    /**
     * @param NemoWidgetService $service
     * @param $q
     * @param null $iataCode
     * @return NemoWidgetGuide
     */
    public function autocomplete(NemoWidgetService $service, $q, $iataCode = null)
    {
        $result = $service->autocomplete($q);

        return new NemoWidgetGuide($result);
    }
}