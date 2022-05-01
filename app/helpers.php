<?php

if (!function_exists("custom_dispatch")) {
    function custom_dispatch($job): int {
        return app(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatch($job);
    }
}
