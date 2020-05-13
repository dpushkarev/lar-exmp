<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Flush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "tools:flush-all";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Clear all cache";

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
        shell_exec('cd /da/src');
        $commands = ['clear-compiled', 'cache:clear', 'view:clear', 'config:clear', 'route:clear'];
        foreach ($commands as $command) {
            $command = "php artisan {$command}";
            system($command);
        }
        $this->info('Flushed all');
    }
}
