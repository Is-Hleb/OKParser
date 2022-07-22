<?php

namespace App\Services;

class IriApi {
    public function getTaskEUnExistedUrls() : array
    {
        $url = "https://ctr-new.tw1.ru/api/posts/unloading?token=obcbT6FXWZi1w5lJH9ZXuFAC3NMjySjdFvDWVoxJKJaY0zn0SG";
        $r = \Illuminate\Support\Facades\Http::withBasicAuth('ibd', 'oIQ4W5FbqB1goKrrp7Gl')->get($url);
        $data = json_decode($r->body());
        $data = array_filter($data, fn($item) => strpos($item[1], "ok.ru"));
        $input = array_map(fn($item) => implode(";", $item), $data);


        $tabsUrls = \App\Models\CronTaskinfo::all()->map(fn($tab) => $tab->signature['urls'] ?? [])->all();
        $existedUrls = [];
        foreach ($tabsUrls as $arr) {
            $existedUrls = array_merge($existedUrls, $arr);
        }
        $issetUrls = [];
        foreach ($existedUrls as $url) {
            $issetUrls[$url] = $url;
        }


        return array_filter($input, fn($url) => !isset($issetUrls[$url]));
    }

    public function getAllLinks() : array
    {
        $url = "https://ctr-new.tw1.ru/api/posts/unloading?token=obcbT6FXWZi1w5lJH9ZXuFAC3NMjySjdFvDWVoxJKJaY0zn0SG";
        $r = \Illuminate\Support\Facades\Http::withBasicAuth('ibd', 'oIQ4W5FbqB1goKrrp7Gl')->get($url);
        return json_decode($r->body()) ?? [];
    }
}
