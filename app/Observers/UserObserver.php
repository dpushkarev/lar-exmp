<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserTravelAgency;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

class UserObserver
{
    public function created(User $model)
    {
        if ($model->isTravelAgency()) {
            $travelAgencyId = request()->get('userTravelAgency_travel_agency_id');
            $this->setTravelAgency($model, $travelAgencyId);
        }
    }

    public function updated(User $model)
    {
        if ($model->isTravelAgency()) {
            $travelAgencyId = request()->get('userTravelAgency_travel_agency_id');
            $this->setTravelAgency($model, $travelAgencyId);
        }
    }

    private function setTravelAgency(User $model, $travelAgencyId)
    {
        if (!$travelAgencyId) {
            self::validationError('userTravelAgency.travel_agency_id', 'Travel agency is required field');
        }
        if (!is_null($model->userTravelAgency)) {
            $model->userTravelAgency->travel_agency_id = $travelAgencyId;
            $model->userTravelAgency->save();
            return;
        }

        UserTravelAgency::forceCreate([
            'travel_agency_id' => $travelAgencyId,
            'user_id' => $model->id,
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
