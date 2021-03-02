<?php


namespace App\Services\PlatformRule;


use Illuminate\Support\Carbon;

class DateCheckHandler extends AbstractHandler
{
    public function check($collection): bool
    {
        if (!is_null($from_date = $this->rule->from_date)) {
            if (Carbon::now()->lessThan($from_date)){
                return false;
            }
        }

        if (!is_null($to_date = $this->rule->to_date)) {
            if (Carbon::now()->greaterThan($to_date)){
                return false;
            }
        }

        echo __CLASS__ . PHP_EOL;
        return parent::check($collection);
    }
}