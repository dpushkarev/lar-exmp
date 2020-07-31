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

                if (!isset($request['passengers'])) {
                    $validator->errors()->add('request', 'Passenger was not passed');
                    return;
                }

                if (!isset($request['address'])) {
                    $validator->errors()->add('request', 'Address was not passed');
                    return;
                }

                if (!isset($request['airSolutionKey'])) {
                    $validator->errors()->add('request', 'Air Solution Key was not passed');
                    return;
                }

                if (!isset($request['email'])) {
                    $validator->errors()->add('request', 'Email was not passed');
                    return;
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
            $this->postRequest['email']
        );
    }

}
