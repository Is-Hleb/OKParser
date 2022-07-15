<?php

namespace App\Console\Commands;

use App\Models\OkUser;
use App\Models\Proxy;
use Illuminate\Console\Command;

class DistribOkUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'distrib:ok_users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return int
     */
    public function handle()
    {
        $users = OkUser::where('proxy_id', null)->get();
        $proxies = Proxy::all()->toArray();

        $index = 0;
        foreach ($users as $user) {
            $user->proxy_id = $proxies[$index++ % sizeof($proxies)]['id'];
            $user->save();
        }

        return 0;
    }
}
