<?php

namespace App\Console\Commands\Payment;

use App\Models\NestpayPayment;
use Carbon\Carbon;
use Cubes\Nestpay\MerchantService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class Processing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "payment:processing";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Processing payment over API";

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /** @var Collection $payments */
        $payments = NestpayPayment::where('processed', '!=', 1)
            ->where('created_at', '>', Carbon::now()->subDay())
            ->get();

        $processed = 0;
        $payments->each(function ($item) use ($processed) {
            $payment = $this->paymentService->paymentProcessOverNestpayApi($item->oid);
            if ($payment->isProcessed()) {
                $processed++;
            }

            sleep(3);
        });

        $this->comment(sprintf('Updated %d from %d', $processed, $payments->count()));
    }
}
