<?php


namespace App\Services;


use App\Dto\FlightsSearchRequestDto;
use App\Dto\FlightsSearchResultsDto;
use App\Exceptions\ApiException;
use App\Exceptions\TravelPortException;
use App\Facades\TP;
use App\Models\Airline;
use App\Models\Country;
use App\Models\FlightsSearchRequest;
use App\Models\VocabularyName;
use App\Models\FlightsSearchRequest as FlightsSearchRequestModel;

class NemoWidgetService
{
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
     * @return FlightsSearchResultsDto
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
            $lfs = TP::LowFareSearchReq($requestDto);
            $request->transaction_id = $lfs->getTransactionId();
            $request->save();

            return new FlightsSearchResultsDto(
                $requestDto,
                $lfs
            );

        } catch (TravelPortException $exception) {
            throw ApiException::getInstance($exception->getMessage());
        }
    }

}