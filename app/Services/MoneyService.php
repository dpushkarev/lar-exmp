<?php


namespace App\Services;


use Libs\Money;

class MoneyService
{
    const AGENCY_CHARGE_AMOUNT = 495;
    const BRAND_CHARGE_AMOUNT = 955;
    const AGENCY_CHARGE_CURRENCY = 'RSD';

    const CASH_AMOUNT = 795;
    const CASH_CURRENCY = 'RSD';

    const INTESA_COMMISSION = 0;
    const PAYPAL_COMMISSION = 2.9;
    const PAYPAL_COMMISSION_FIX = 30;
    const PAYPAL_CURRENCY = 'RSD';

    public function getMoneyByString($string): Money
    {
        if (is_null($string)) return Money::zero(static::AGENCY_CHARGE_CURRENCY);

        return Money::of(substr($string, 3), substr($string, 0, 3));
    }

    public function getAgencyChargeForAllPassengers($count)
    {
        return $this->getAgencyChargeMoney()->multipliedBy($count);
    }

    public function calculateIntesaPrice(Money $money)
    {
        return $money->multipliedBy(static::INTESA_COMMISSION)->dividedBy(100, 2);
    }

    public function calculateCashPrice($count)
    {
        return $this->getCashMoney()->multipliedBy($count);
    }

    /**
     * @param Money $money
     * @return Money
     * @throws \Brick\Money\Exception\MoneyMismatchException
     */
    public function calculatePayPalPrice(Money $money)
    {
        return $money->multipliedBy(static::PAYPAL_COMMISSION)
            ->dividedBy(100, 2)
            ->plus($this->getPayPalFixCommissionMoney());
    }

    protected function getAgencyChargeMoney(): Money
    {
        return Money::of(static::AGENCY_CHARGE_AMOUNT, static::AGENCY_CHARGE_CURRENCY);
    }

    protected function getAgencyChargeMoneyByPassengerType($type): Money
    {
        return [
            'ADT' => Money::of(static::AGENCY_CHARGE_AMOUNT, static::AGENCY_CHARGE_CURRENCY),
            'CNN' => Money::of(static::AGENCY_CHARGE_AMOUNT, static::AGENCY_CHARGE_CURRENCY),
            'INF' => Money::of(static::AGENCY_CHARGE_AMOUNT, static::AGENCY_CHARGE_CURRENCY)
        ][$type];
    }

    protected function getCashMoney(): Money
    {
        return Money::of(static::CASH_AMOUNT, static::CASH_CURRENCY);
    }

    protected function getPayPalFixCommissionMoney(): Money
    {
        return Money::of(static::PAYPAL_COMMISSION_FIX, static::PAYPAL_CURRENCY);
    }

    public function addAgencyChargeByPassengerType(Money $money, $type = 'ADT')
    {
        return $money->plus($this->getAgencyChargeMoneyByPassengerType($type));
    }

}

