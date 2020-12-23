<?php


namespace App\Rules;

use App\Models\FrontendDomain;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class CheckMatching implements Rule
{
    protected $user_id;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }

    public function passes($attribute, $value)
    {
        /** @var User $user */
        $user = User::findOrFail($this->user_id);
        $userTravelAgencyId = $user->userTravelAgency->travel_agency_id;

        $frontendDomain = FrontendDomain::findOrFail($value);
        $domainTravelAgency = $frontendDomain->travel_agency_id;

        return ($userTravelAgencyId == $domainTravelAgency);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('The domain does not matched the user\'s agency.');
    }
}