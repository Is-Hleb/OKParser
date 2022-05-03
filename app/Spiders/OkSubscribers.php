<?php

namespace App\Spiders;

use Generator;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;
use Symfony\Component\DomCrawler\Crawler;

class OkSubscribers extends BasicSpider
{
    public array $startUrls = [
        //
    ];

    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];

    public array $spiderMiddleware = [
        //
    ];

    public array $itemProcessors = [
        //
    ];

    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    public int $page = 1;

    public int $concurrency = 2;

    public int $requestDelay = 1;

    /**
     * @return Generator<ParseResult>
     */
    public function parse(Response $response): Generator
    {
        $result = $response->filter('li.item.it > .wide-user')->each(function (Crawler $node, $i) {
            $link = $node->filter('a.u-ava')->first()->attr('href');
            $id = explode('/', $link);
            $id = end($id);
            $id = substr($id, 0, strpos($id, '?'));
            return $id;
        });

        $this->page += 1;
        $nextUrl = self::getInitialUrl($this->context['user_id'], $this->page);

        yield $this->item( $result );
        if (!emptyArray($result)) {
            yield $this->request(
                'GET',
                $nextUrl,
                'parse'
            );
        }
        
    }

    public static function getInitialUrl(int $user_id, int $page) : string
    {
        return "https://m.ok.ru/dk?st.cmd=friendFriends&st.sbr=on&st.friendId=$user_id&st.sbs=off&st.page=$page&st.dir=FORWARD&_prevCmd=friendFriends";
    }
}
