<?php

namespace App\Console\Commands;

use App\Services\OKApi;
use Illuminate\Console\Command;

class ParsePostsExtra extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:extra';

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
        $json = file_get_contents(__DIR__ . '/posts.json');
        $json = json_decode($json, true);
        $urls = file_get_contents(__DIR__ . '/posts.txt');
        $urls = explode("\n", $urls);
        $api = new OKApi();

        $count = 0;
        foreach ($urls as $url) {
            if(!isset($json[$url])) {
                dump($url);
                $json[$url] = $api->getPostUserActivity($url);
                echo "OK\n";
                file_put_contents(__DIR__ . '/posts.json', json_encode($json));
            } else {
                $count ++;
                dump($count);
            }
        }

        return 0;
    }
}
