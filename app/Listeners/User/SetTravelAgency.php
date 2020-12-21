<?php


namespace App\Listeners\User;


use App\Models\User;
use App\Models\UserTravelAgency;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

class SetTravelAgency
{
    public function handle($event)
    {
        /** @var User $user */
        $user = $event->user;

        if ($user->belongsToTravelAgency()) {
            $travelAgencyId = request()->get('userTravelAgency_travel_agency_id');
            $this->setTravelAgency($user, $travelAgencyId);
        }
    }

    private function setTravelAgency(User $user, $travelAgencyId)
    {
        if (!$travelAgencyId) {
            self::validationError('userTravelAgency.travel_agency_id', 'Travel agency is required field');
        }
        
        if (!auth()->user()->isGod() &&
            $travelAgencyId != auth()->user()->userTravelAgency->travel_agency_id
        ) {
            self::validationError('userTravelAgency.travel_agency_id', 'Travel agency is incorrect');
        }

        if (!is_null($user->userTravelAgency)) {
            $user->userTravelAgency->travel_agency_id = $travelAgencyId;
            $user->userTravelAgency->save();
            return;
        }

        UserTravelAgency::forceCreate([
            'travel_agency_id' => $travelAgencyId,
            'user_id' => $user->id,
        ]);

        return;
    }

    private static function validationError($key, $message)
    {
        $messageBag = new MessageBag();
        $messageBag->add($key, __($message));

        throw ValidationException::withMessages($messageBag->getMessages());
    }
}