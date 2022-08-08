<?php

namespace App\Http\Controllers\Api;

use App\Models\TaskE;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class VkTaskEController
{
    public function __invoke(Request $request)
    {
        $data = $request->input('data');

        foreach ($data as $datum) {
            TaskE::create($datum);
        }
        Artisan::call('task:e:export');
    }
}
