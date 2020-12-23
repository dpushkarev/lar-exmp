<?php


namespace App\Events\User;


use App\Models\UserTravelAgency;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTravelAgencyUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $model;

    public function __construct(UserTravelAgency $userTravelAgency)
    {
        $this->model = $userTravelAgency;
    }
}