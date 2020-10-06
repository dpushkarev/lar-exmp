<?php

namespace App\Providers;

use Cubes\Nestpay\Laravel\NestpayHandleUnprocessedPaymentCommand;
use Cubes\Nestpay\Laravel\PaymentDaoEloquent;
use Illuminate\Support\ServiceProvider;
use Cubes\Nestpay\MerchantService;

class NestpayServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('NP', function ($app) {
            $paymentModelClass = config('nestpay.paymentModel');

            if (empty($paymentModelClass)) {
                throw new \InvalidArgumentException('Config nestpay.paymentModel must be name of the payment model class');
            }

            $paymentDao = new PaymentDaoEloquent($paymentModelClass);

            $merchantService = new MerchantService([
                'merchantConfig' => config('nestpay.merchant'),
                'paymentDao' => $paymentDao,
            ]);

            $merchantService->onFailedPayment(function ($payment) {
                //just trigger event
                event(new \Cubes\Nestpay\Laravel\NestpayPaymentProcessedFailedEvent($payment));
            })->onSuccessfulPayment(function($payment) {
                //just trigger event
                event(new \Cubes\Nestpay\Laravel\NestpayPaymentProcessedSuccessfullyEvent($payment));
            })->onError(function($payment, $ex) {
                //just trigger event
                event(new \Cubes\Nestpay\Laravel\NestpayPaymentProcessedErrorEvent($payment, $ex));

                if (config('nestpay.throwExceptions')) {
                    throw $ex;
                }
            });

            return $merchantService;
        });

    }
}