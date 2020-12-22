<?php


namespace App\Listeners\User;


use App\Exceptions\NovaException;
use App\Models\User;
use App\Models\UserTravelAgency;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

class SetTravelAgency
{
    /**
     * @param $event
     * @return bool
     * @throws NovaException
     * @throws \App\Exceptions\ApiException
     */
    public function handle($event)
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        if ($currentUser->isGod()) {
            return true;
        }

        if (is_null($currentUser->userTravelAgency)) {
            throw NovaException::getInstance('The travel agency has not been bind');
        }

        /** @var User $user */
        $user = $event->user;

        UserTravelAgency::forceCreate([
            'travel_agency_id' => $currentUser->userTravelAgency->travel_agency_id,
            'user_id' => $user->id,
        ]);
    }

    private static function validationError($key, $message)
    {
        $messageBag = new MessageBag();
        $messageBag->add($key, __($message));

        throw ValidationException::withMessages($messageBag->getMessages());
    }
}