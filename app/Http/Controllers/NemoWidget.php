<?php

namespace App\Http\Controllers;

use App\Http\Resources\NemoWidget\AirlinesAll;
use App\Http\Resources\NemoWidget\Autocomplete;
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
     * @return Autocomplete
     */
    public function autocomplete(NemoWidgetService $service, $q, $iataCode = null)
    {
        $result = $service->autocomplete($q);

        return new Autocomplete($result);
    }

    public function airlinesAll(NemoWidgetService $service)
    {
        $airlines = $service->airlinesAll();
        $countries = $service->countriesAll();

        $result = collect(['countries' => $countries, 'airlines' => $airlines]);

        return new AirlinesAll($result);
    }
}