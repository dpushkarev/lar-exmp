<?php


namespace App\Http\Requests;

use App\Dto\FlightsSearchRequestDto;
use App\Exceptions\ApiException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

/**
 * Class TravelPortSearchRequest
 * @package App\Http\Requests
 */
class FlightsSearchRequest extends FormRequest
{
    private $postRequest;

    public function rules()
    {
        return [
            'request' => 'required|json',
        ];
    }

    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return [

        ];
    }

    /**
     * @param Validator $validator
     * @throws ApiException
     */
    protected function failedValidation(Validator $validator)
    {
        throw ApiException::getInstanceValidate($validator->errors()->first());
    }

    /**
     * @param Validator $validator
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (empty($validator->failed())) {
                $request = json_decode($validator->getData()['request'], true);

                if (!isset($request['segments'])) {
                    $validator->errors()->add('request', 'Segment was not passed');
                    return;
                }

                if (!isset($request['passengers'])) {
                    $validator->errors()->add('request', 'Passenger was not passed');
                    return;
                }

                $request['parameters']['searchType'] = [
                    1 => 'OW',
                    2 => 'RT',
                ][count($request['segments'])] ?? 'CR';

                $request['parameters']['flightNumbers'] = [];
                $request['parameters']['priceRefundType'] = null;

                $this->postRequest = $request;
            }
        });

    }

    /**
     * @return FlightsSearchRequestDto
     */
    public function getFlightsSearchRequestDto()
    {
        return new FlightsSearchRequestDto(
            $this->postRequest['segments'],
            $this->postRequest['passengers'],
            $this->postRequest['parameters'] ?? null
        );
    }

}
