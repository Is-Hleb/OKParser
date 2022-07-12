<?php

namespace Database\Seeders;

use App\Models\CountryCode;
use App\Models\Proxy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

//        $codes = [
//            [
//                'code' => 10415971874,
//                'name' => 'KZ'
//            ],
//            [
//                'code' => 10424076448,
//                'name' => 'UA'
//            ],
//        ];
//
//        CountryCode::insert($codes);

        $proxies = "https://hlebishenko_gmail_co:a2e8c6660d@83.171.214.221:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.234.106:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.214.92:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.215.166:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.234.24:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.233.150:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.215.17:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.233.107:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.234.5:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.234.1:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.16.166:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.19.135:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.17.145:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.17.83:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.19.155:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.34.48:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.33.154:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.34.55:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.34.220:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.33.162:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.117.176:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.117.201:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.119.24:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.119.38:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.116.19:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.118.11:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.118.233:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.116.106:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.117.237:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.118.60:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.119.199:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.232.116.169:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.213.129:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.214.69:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.233.67:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.234.32:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.235.189:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.233.122:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.235.232:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.213.71:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.215.211:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.234.130:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.213.105:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.215.250:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.234.164:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.214.30:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.213.74:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.234.151:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.232.67:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.235.79:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.233.236:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.213.142:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.235.200:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.233.12:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.212.239:30016
https://hlebishenko_gmail_co:a2e8c6660d@83.171.235.240:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.89.180:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.88.230:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.90.203:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.89.226:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.88.209:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.91.21:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.91.100:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.88.16:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.88.129:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.91.142:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.88.76:30016
https://hlebishenko_gmail_co:a2e8c6660d@213.166.91.189:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.33.31:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.32.252:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.34.212:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.35.7:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.34.92:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.32.51:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.35.176:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.34.234:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.34.79:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.33.171:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.35.54:30016
https://hlebishenko_gmail_co:a2e8c6660d@212.81.33.185:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.17.183:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.16.25:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.19.198:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.16.182:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.19.78:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.16.176:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.17.174:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.17.136:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.18.210:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.18.33:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.18.85:30016
https://hlebishenko_gmail_co:a2e8c6660d@84.54.17.163:30016
https://hlebishenko_gmail_co:a2e8c6660d@45.128.229.103:30016
https://hlebishenko_gmail_co:a2e8c6660d@45.128.231.217:30016
https://hlebishenko_gmail_co:a2e8c6660d@45.128.228.123:30016
https://hlebishenko_gmail_co:a2e8c6660d@45.128.229.52:30016
https://hlebishenko_gmail_co:a2e8c6660d@45.128.228.215:30016
https://hlebishenko_gmail_co:a2e8c6660d@45.128.231.30:30016
https://hlebishenko_gmail_co:a2e8c6660d@45.128.229.164:30016
https://hlebishenko_gmail_co:a2e8c6660d@45.128.231.201:30016";

        $array = explode("\n", $proxies);
        foreach($array as $item) {
            $item = explode("https://", $item);
            $item = $item[1];
            $temp = explode("@", $item);
            $ip = $temp[1];
            dump($ip);
            $user_password = explode(':', $temp[0]);
            $user = $user_password[0];
            $password = $user_password[1];

            Proxy::create([
                'user' => $user,
                'password' => $password,
                'ip' => $ip
            ]);
        }

    }
}
