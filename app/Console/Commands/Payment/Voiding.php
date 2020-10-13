<?php

namespace App\Console\Commands\Payment;

use Cubes\Nestpay\MerchantService;
use Illuminate\Console\Command;

class Voiding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "
        payment:voiding
        {oid? : Oid of payment}
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Void payment over API";

    protected $paymentService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(MerchantService $merchantService)
    {
        parent::__construct();
        $this->paymentService = $merchantService;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $oid = $this->argument('oid');
        $result = $this->paymentService->voidOverNestpayApi($oid);

        print_r($result);
    }
}
