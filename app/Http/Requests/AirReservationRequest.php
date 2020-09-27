<?php


namespace App\Http\Requests;

use App\Dto\AirReservationRequestDto;
use App\Exceptions\ApiException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Class AirReservationRequest
 * @package App\Http\Requests
 */
class AirReservationRequest extends FormRequest
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

                $validatorInstance = \Illuminate\Support\Facades\Validator::make($request, [
                    'passengers' => 'required',
                    'email' => 'required|email',
                    'airSolutionKey' => 'required|string|max:50',
                    'phoneNumber' => 'required|string|max:50',
                    'address.country' => 'required|string|max:50',
                    'address.city' => 'required|string|max:50',
                    'address.postalCode' => 'required|integer',
                    'address.street' => 'required|string|max:50',
                    'paymentOption' => 'required|in:card,bank',
                ]);

                if ($validatorInstance->fails()) {
                    $validator->errors()->add('request', $validatorInstance->errors()->first());
                    return;
                }

                foreach($request['passengers'] as &$passenger) {
                    $validatorInstance = \Illuminate\Support\Facades\Validator::make($passenger, [
                        'travelerType' => 'required|string|max:3',
                        'first' => 'required|string|max:50',
                        'last' => 'required|string|max:50',
                        'prefix' => 'required|string|max:10',
                        'dob' => 'required_if:travelerType,INF|date'
                    ]);

                    if ($validatorInstance->fails()) {
                        $validator->errors()->add('request', $validatorInstance->errors()->first());
                        return;
                    }

                    $passenger['key'] = base64_encode(rand(10000000, 20000000));
                }

                if (!PhoneNumberUtil::isViablePhoneNumber($request['phoneNumber'])) {
                    $validator->errors()->add('request', 'Phone number was not valid');
                    return;
                }

                try {
                    $phoneUnit = PhoneNumberUtil::getInstance();
                    $phone = $phoneUnit->parse("+" . $request['phoneNumber'], PhoneNumberFormat::INTERNATIONAL);
                    list($phonePieces['country'], $phonePieces['area'], $phonePieces['number']) = explode(' ', $phoneUnit->format($phone, PhoneNumberFormat::INTERNATIONAL));
                    $request['phoneNumber'] = $phonePieces;
                } catch (NumberParseException $exception) {
                    $validator->errors()->add('request', $exception->getMessage());
                    return;
                }

                $this->postRequest = $request;
            }
        });

    }

    /**
     * @return AirReservationRequestDto
     */
    public function getAirReservationRequestDto()
    {
        return new AirReservationRequestDto(
            $this->postRequest['passengers'],
            $this->postRequest['address'],
            $this->postRequest['airSolutionKey'],
            $this->postRequest['phoneNumber'],
            $this->postRequest['email'],
            $this->postRequest['paymentOption']
        );
    }

}
