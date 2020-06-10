<?php


namespace App\Services;


use App\Adapters\TravelPortAdapter;
use App\Dto\FlightsSearchRequestDto;
use App\Exceptions\ApiException;
use App\Exceptions\TravelPortException;
use App\Facades\TP;
use App\Models\Airline;
use App\Models\Country;
use App\Models\FlightsSearchRequest;
use App\Models\VocabularyName;
use App\Models\FlightsSearchRequest as FlightsSearchRequestModel;
use Illuminate\Support\Facades\Cache;

class NemoWidgetService
{
    protected $travelPortAdapter;

    public function __construct(TravelPortAdapter $travelPortAdapter)
    {
        $this->travelPortAdapter = $travelPortAdapter;
    }

    public function autocomplete($q, $iataCode = null)
    {
        $result = VocabularyName::cacheStatic('getByName', $q);

        if (null !== $iataCode) {
            $result = $result->reject(function ($element) use ($iataCode) {
                return $element->nameable->code === $iataCode;
            });
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function airlinesAll()
    {
        return Airline::cacheStatic('getAll');
    }

    /**
     * @return mixed
     */
    public function countriesAll()
    {
        return Country::cacheStatic('getAll');
    }

    /**
     * @param FlightsSearchRequestDto $dto
     */
    public function flightsSearchRequest(FlightsSearchRequestDto $dto)
    {
        $fsrModel = FlightsSearchRequestModel::forceCreate([
            'data' => $dto
        ]);

        $dto->setRequestId($fsrModel->id);
    }

    /**
     * @param int $id
     * @return \Illuminate\Support\Collection|mixed
     * @throws ApiException
     * @throws TravelPortException
     */
    public function flightsSearchResult(int $id)
    {
        $request = FlightsSearchRequest::find($id);

        if(null === $request) {
            throw ApiException::getInstanceInvalidId($id);
        }

        $requestDto = new FlightsSearchRequestDto(
            $request->data['segments'],
            $request->data['passengers'],
            $request->data['parameters'],
            $id
        );

        try{
            $lowFareSearchRsp = Cache::rememberForever('result'. $id, function () use ($requestDto, $id) {
                return TP::LowFareSearchReq($requestDto);
            });

            $response = $this->travelPortAdapter->LowFareSearch($lowFareSearchRsp);
            $response->put('request', $requestDto);

            $request->transaction_id = $lowFareSearchRsp->getTransactionId();
            $request->save();

            return $response;

        } catch (TravelPortException $exception) {
            throw ApiException::getInstance($exception->getMessage());
        }
    }

}