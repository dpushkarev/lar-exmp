<?php

namespace App\Console\Commands;

use App\Models\Error;
use App\Models\FlightsSearchFlightInfo;
use App\Models\FlightsSearchRequest;
use App\Models\FlightsSearchResult;
use App\Models\Reservation;
use Illuminate\Console\Command;

class FlushTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "tools:flush-tables";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Clear tables";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Reservation::query()->truncate();
        FlightsSearchFlightInfo::query()->truncate();
        FlightsSearchResult::query()->truncate();
        FlightsSearchRequest::query()->truncate();
        Error::query()->truncate();

        $this->comment('Tables have been cleared');
    }
}
