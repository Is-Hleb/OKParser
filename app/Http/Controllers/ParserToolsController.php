<?php

namespace App\Http\Controllers;

use App\Models\OkUser;
use App\Models\Proxy;

class ParserToolsController extends Controller
{
    public function resetProxies()
    {
        Proxy::query()->update(['blocked' => 0]);
        return redirect()->back();
    }

    public function resetUsers()
    {
        OkUser::query()->update(['blocked' => 0]);
        return redirect()->back();
    }
}
