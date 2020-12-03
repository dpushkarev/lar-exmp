<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class ElevateToGod extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "
        tools:elevate-to-god
        {user_id? : Id of user}
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Make user a god";

    protected $paymentService;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user_id = $this->argument('user_id');

        if (is_null($user_id)) {
            $this->warn('User id is required!');
            return;
        }

        $user = User::find($user_id);
        $user->type = User::GOD_TYPE;
        $user->save();

        $this->info('Amen!');
    }
}
