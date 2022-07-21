<?php

namespace App\Http\Controllers\Api;

use App\Models\TaskE;
use Illuminate\Http\Request;

class VkTaskEController
{
    public function __invoke(Request $request)
    {
        $data = $request->input('data');

        foreach ($data as $datum) {
            TaskE::create($datum);
        }
    }
}
