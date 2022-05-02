<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JobInfo;

class IndexController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'jobs' => JobInfo::all()
        ]);
    }
}
