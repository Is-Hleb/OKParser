<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class S3Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 's3:test';

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
        dump(now("UTC")->format("Ymd"));
        $login = "103784_asup_media";
        $pass = "5t=BUw{S%X";
        $dateKey = hash_hmac('sha256', "AWS4".$pass, now("UTC")->format("Ymd"));
        $DateRegionKey = hash_hmac('sha256', $dateKey, "ru-1");
        $DateRegionServiceKey = hash_hmac('sha256', $DateRegionKey, "s3");
        $SigningKey = hash_hmac('sha256', $DateRegionServiceKey, "aws4_request");
        $signature = hash_hmac('sha256', $SigningKey, $pass);

        $date = now("UTC")->format('md');
        $result = Http::withHeaders([
            "Authorization" => "AWS4-HMAC-SHA256",
            "Credential" => "$login/$date/ru-1/s3/aws4_request",
            "SignedHeaders" => "host;range;x-amz-date",
            "Signature" => $signature
        ])->get("https://s3.storage.selcloud.ru/Asup_media");
        $data = new SimpleXMLElement($result->body());
        dump($data);
        dump($dateKey);
    }
}
