<?php


namespace App\Listeners\User;


class SetCreator
{
    public function handle($event)
    {
        $event->user->created_by = auth()->user()->id;
    }

}