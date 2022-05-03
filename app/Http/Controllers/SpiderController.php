<?php

namespace App\Http\Controllers;

use App\Spiders\OkSubscribers;
use Illuminate\Http\Request;
use RoachPHP\Roach;
use RoachPHP\Spider\Configuration\Overrides;

class SpiderController extends Controller
{
    public function __invoke()
    {
        $user_id = 514677397371;
        $url = OkSubscribers::getInitialUrl($user_id, 1);

        $output = Roach::collectSpider(
            OkSubscribers::class,
            new Overrides([$url]),
            context: ['user_id' => $user_id]
        );
        dd($output);
    }
}
