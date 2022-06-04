<?php

namespace App\Http\Controllers;

use App\Models\OkUser;
use PHPHtmlParser\Dom;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Puphpeteer\Resources\Browser;
use Nesk\Puphpeteer\Resources\Page;
use Nesk\Rialto\Data\JsFunction;
use App\Models\ApiToken;
use App\Services\OKApi;
use Exception;

use function PHPSTORM_META\map;

class TestController extends Controller
{
    public function __invoke(OKApi $api) {
        $res = $api->getFriendsByApi(377097626553);
        dump($res);
    }
}
