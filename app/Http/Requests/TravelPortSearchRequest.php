<?php


namespace App\Http\Requests;

use App\Dto\TravelPortSearchDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class TravelPortSearchRequest
 * @package App\Http\Requests
 */
class TravelPortSearchRequest extends FormRequest
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
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
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

                $this->postRequest = $request;
            }
        });

    }

    /**
     * @return TravelPortSearchDto
     */
    public function getTravelPortSearchDto()
    {
        return new TravelPortSearchDto(
            $this->postRequest['segments'],
            $this->postRequest['passengers'],
            $this->postRequest['parameters'] ?? null
        );
    }

}
