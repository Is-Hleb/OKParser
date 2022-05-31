<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\OkUser;
use RoachPHP\Roach;
use App\Spiders\OkSubscribers;
use Exception;
use Illuminate\Support\Facades\DB;
use RoachPHP\Spider\Configuration\Overrides;
use PHPHtmlParser\Dom;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Puphpeteer\Resources\Page;
use Nesk\Rialto\Data\JsFunction;

class OKApi
{
    private const BASE_URL = "https://api.ok.ru/fb.do?";
    public const TYPES = [
        'CHAT','CITY_NEWS','GROUP_MOVIE', 'GROUP_PHOTO',
        'GROUP_PRODUCT','GROUP_TOPIC','HAPPENING_TOPIC',
        'MOVIE','OFFER','PRESENT','SCHOOL_FORUM','SHARE',
        'USER_ALBUM','USER_FORUM','USER_PHOTO','USER_PRODUCT',
        'USER_STATUS',
    ];
    public const ACTIONS = [
        'getPostsByUser','getGroupFollowers',
        'getUserInfo','getPostInfoById','getPostComments',
        'getPostLikes','getSubscribersIds', 'getPostsByGroup'
    ];

    public int $id;
    private string $appKey;
    private string $key;
    private string $secret;
    private OkUser $user;

    public static function validationRules(): array
    {
        return [
            'getPostsByGroup' => [
                'url' => 'required',
                'limit' => 'required'
            ],
            'getGroupFollowers' => [
                'id' => 'required',
                'anchor'
            ],
            'getUserInfo' => [
                'ids' => 'required',
            ],
            'getPostInfoByUrl' => [
                'url' => 'required',
            ],
            'getPostComments' => [
                'id' => 'required',
            ],
            'getPostLikes' => [
                'id' => 'required',
                'limit' => 'required'
            ],
            'getUserSubscribersIds' => [
                'user_id' => 'required'
            ],
            'getPostInfoById' => [
                'id' => 'required'
            ],
            'getPostsByUser' => [
                'url' => 'required',
                'limit' => 'required'
            ]
        ];
    }

    public function __construct()
    {
        $okToken = ApiToken::inRandomOrder()->first();
        $this->setAnotherUser();

        $this->appKey = $okToken->app_key;
        $this->key = $okToken->key;
        $this->secret = $okToken->secret;
    }

    public function getUserSubscribersIds($user_id)
    {
        $url = OkSubscribers::getInitialUrl($user_id, 1);
        $output = Roach::collectSpider(
            OkSubscribers::class,
            new Overrides([$url]),
            context: ['user_id' => $user_id]
        );
        $result = [];
        foreach ($output as $data) {
            $result = array_merge($result, $data->all());
        }
        return $result;
    }

    public function rules(): array
    {
        return [
            // name, email, subject and body are required
            [['id'], 'required'],
        ];
    }

    public function getPostsByGroup($url, $limit)
    {
        $puppeteer = new Puppeteer([
            'executable_path' => config('puppeter.node_path'),
        ]);
        $browser = $puppeteer->launch([
            // 'headless' => false
        ]);


        $page = $browser->newPage();
        if (!$this->user->cookies) {
            $page = $this->relogin($page, $url);
        } else {
            $cookies = json_decode($this->user->cookies, JSON_OBJECT_AS_ARRAY);
            $page->setCookie(...$cookies['cookies']);
            $page->goto($url);

            $dom = new DOM;
            $dom->loadStr($page->content());
            $flag = $dom->find('#hook_Block_ContentUnavailableForAnonymMRB', 0);
            $mustLogin = $dom->find('div.close-button__akasx', 0);
            if ($mustLogin) {
                $page = $this->relogin($page, $url);
            }
            if ($flag) {
                $page = $this->relogin($page, $url);
            }
        }

        $page->evaluate(JsFunction::createWithBody("
        async function subscribe() {
            let response = await await new Promise(resolve => {
                    const distance = 100; // should be less than or equal to window.innerHeight
                    const delay = 100;
                    const timer = setInterval(() => {
                    document.scrollingElement.scrollBy(0, distance);
                    if (document.scrollingElement.scrollTop + window.innerHeight >= document.								scrollingElement.scrollHeight) {
                        clearInterval(timer);
                        resolve();
                    }
                    }, delay);
                    });
            await subscribe();
        }
          subscribe();
        "));
        
        $dom = new Dom;
        $iterations = 0;
        ini_set('max_execution_time', 0);
        do {
            $posts = [];
            $dom->loadStr($page->content());
            $loadMore = $dom->find('a.js-show-more.link-show-more', 0);
            $loadMoreContainer = $dom->find('div.loader-container', 0);

            if ($loadMore && !$loadMoreContainer) {
                $page->click('a.js-show-more.link-show-more');
            }
            $postsHtml = $dom->find('.feed-w');
            foreach ($postsHtml as $postHtml) {
                $jsInfo = $postHtml->find('.feed_cnt', 0);
                $info = explode(',', $jsInfo->getAttribute('data-l'));
                if (sizeof($info) === 4) {
                    $info = [
                        'topicId' => $info[1],
                        'groupId' => $info[3]
                    ];
                    $posts[$info['topicId']] = $info['topicId'];
                }
            }
            if ($iterations++ > $limit) {
                break;
            }
            sleep(2);
        } while (sizeof($posts) < $limit);

        $browser->close();
        return array_values($posts);
    }

    public function getPostsByUser($url, $limit)
    {
        $puppeteer = new Puppeteer([
            'executable_path' => config('puppeter.node_path'),
        ]);
        $browser = $puppeteer->launch();


        $page = $browser->newPage();
        if (!$this->user->cookies) {
            $page = $this->relogin($page, $url);
        } else {
            $cookies = json_decode($this->user->cookies, JSON_OBJECT_AS_ARRAY);
            $page->setCookie(...$cookies['cookies']);
            $page->goto($url, [
                "waitUntil" => 'networkidle0',
            ]);

            $dom = new DOM;
            $dom->loadStr($page->content());
            $flag = $dom->find('#hook_Block_ContentUnavailableForAnonymMRB', 0);
            if ($flag) {
                $page = $this->relogin($page, $url);
            }
            $flag = $dom->find('#hook_Block_AnonymVerifyCaptchaStart', 0);
            if ($flag) {
                $page = $this->relogin($page, $url);
            }
        }

        $page->evaluate(JsFunction::createWithBody("
        async function subscribe() {
            let response = await await new Promise(resolve => {
                    const distance = 100; // should be less than or equal to window.innerHeight
                    const delay = 100;
                    const timer = setInterval(() => {
                    document.scrollingElement.scrollBy(0, distance);
                    if (document.scrollingElement.scrollTop + window.innerHeight >= document.								scrollingElement.scrollHeight) {
                        clearInterval(timer);
                        resolve();
                    }
                    }, delay);
                    });
            await subscribe();
        }
          subscribe();
        "));
        
        $dom = new Dom;
        $iterations = 0;
        ini_set('max_execution_time', 0);
        do {
            $posts = [];
            file_put_contents(storage_path('logs/') . "output$iterations.html", $page->content());
            $dom->loadStr($page->content());

            $postsHtml = $dom->find('.feed-w');
            foreach ($postsHtml as $postHtml) {
                $jsInfo = $postHtml->find('.feed_cnt', 0);
                $info = explode(',', $jsInfo->getAttribute('data-l'));
                if (sizeof($info) === 4) {
                    $info = [
                        'ownerUserId' => $info[1],
                        'topicId' => $info[3]
                    ];
                    if (strlen($info['ownerUserId']) !== 12) {
                        continue;
                    }
                    $posts[$info['topicId']] = $info['topicId'];
                }
            }
            if ($iterations++ > $limit) {
                break;
            }
            sleep(2);
        } while (sizeof($posts) < $limit);
        $browser->close();

        return array_values($posts);
    }

    public function getGroupFollowers($id, $anchor = ""): bool|array
    {
        $anpr = ""; //anchor

        if ($anchor != "") {
            $anpr = "anchor=" . $anchor;
        }

        $method = "group.getMembers";

        $md5 = md5($anpr . "application_key=" . $this->appKey . "count=1000format=jsonmethod=" . $method . "uid=" . $id . $this->secret);

        $params = [
            'application_key' => $this->appKey,
            'count' => 1000,
            'format' => 'json',
            'method' => $method,
            'uid' => $id,
            'sig' => $md5,
            'access_token' => $this->key,
        ];

        if ($anchor != "") {
            $params = array_merge(array('anchor' => $anchor), $params);
        }


        return $this->request($params);
    }

    public function getUserInfo(array|int $ids): bool|array
    {
        if (is_array($ids)) {
            $ids = implode(',', $ids);
        }

        $method = "users.getInfo";

        $md5 = md5("application_key=" . $this->appKey . "fields=age,birthday,first_name,email,last_name,gender,location,name,pic_full,shortnameformat=jsonmethod=" . $method . "uids=" . $ids . $this->secret);

        $params = [
            'application_key' => $this->appKey,
            'fields' => 'age,birthday,first_name,email,last_name,gender,location,name,pic_full,shortname',
            'format' => 'json',
            'method' => $method,
            'uids' => $ids,
            'sig' => $md5,
            'access_token' => $this->key
        ];

        return $this->request($params);
    }

    public function getPostInfoById($id) : array|bool
    {
        foreach (self::TYPES as $type) {
            $method = "discussions.get";

            $md5 = md5("application_key={$this->appKey}discussionId={$id}discussionType={$type}format=jsonmethod={$method}{$this->secret}");

            $params = [
                'application_key' => $this->appKey,
                'discussionId' => $id,
                'discussionType' => $type,
                'format' => 'json',
                'method' => $method,
                'sig' => $md5,
                'access_token' => $this->key
            ];
            $result = $this->request($params);
            if (!empty($result)) {
                $output[] = $result;
            }
        }
        return $output;
    }

   
    public function getPostInfoByUrl($url): array|bool
    {
        $array = explode('/', $url);
        foreach ($array as $key => $item) {
            if (in_array($item, ['topic', 'album'])) {
                $id = $array[$key + 1];
            }
        }
        return $this->getPostInfoById($id);
    }

    public function getPostComments($id, $offset = 0): bool|array
    {
        foreach (self::TYPES as $type) {
            $method = "discussions.getDiscussionComments";

            $md5 = md5("application_key={$this->appKey}count=1000entityId={$id}entityType={$type}format=jsonmethod={$method}offset={$offset}{$this->secret}");

            $params = [
                'application_key' => $this->appKey,
                'count' => 1000,
                'entityId' => $id,
                'entityType' => $type,
                'format' => 'json',
                'method' => $method,
                'offset' => $offset,
                'sig' => $md5,
                'access_token' => $this->key
            ];
            $output[] = $this->request($params);
        }

        return $output;
    }

    public function getPostLikes($id, $limit): bool|array
    {
        foreach (self::TYPES as $type) {
            $anpr = "";
            $anchor = "";

            $method = "discussions.getDiscussionLikes";

            $md5 = md5("{$anpr}application_key={$this->appKey}count=100discussionId={$id}discussionType={$type}format=jsonmethod={$method}{$this->secret}");

            $params = [
                'application_key' => $this->appKey,
                'count' => 100,
                'discussionId' => $id,
                'discussionType' => $type,
                'format' => 'json',
                'method' => $method,
                'sig' => $md5,
                'access_token' => $this->key
            ];

            $answer = $this->request($params);
            if (array_key_exists('error_msg', $answer) || !array_key_exists('users', $answer)) {
                continue;
            } else {
                $output = $answer['users'];
                $anchor = $answer['anchor'];
                while (sizeof($output) < $limit) {
                    
                    $anpr = "anchor=" . $anchor;

                    $method = "discussions.getDiscussionLikes";

                    $md5 = md5("{$anpr}application_key={$this->appKey}count=100discussionId={$id}discussionType={$type}format=jsonmethod={$method}{$this->secret}");

                    $params = [
                        'application_key' => $this->appKey,
                        'count' => 100,
                        'discussionId' => $id,
                        'discussionType' => $type,
                        'format' => 'json',
                        'method' => $method,
                        'sig' => $md5,
                        'access_token' => $this->key
                    ];

                    $params = array_merge(array('anchor' => $anchor), $params);
                    
                    $answer = $this->request($params);
                    if(!empty($answer) && !array_key_exists('error_msg', $answer) && isset($answer['users'])) {
                        $output = array_merge($output, $answer['users']);
                        $anchor = $answer['anchor'];
                    } else {
                        return $output;
                    }
                }
                return $output;
            }
        }
        return [];
    }

    private function relogin($page, $url) : Page
    {
        do {
            $page->goto('https://ok.ru', [
                "waitUntil" => 'networkidle0',
            ]);

            $page->type('#field_email', $this->user->login);
            $page->type('#field_password', $this->user->password);

            $page->click('input[type="submit"]');

            $page->waitForNavigation([
                "waitUntil" => 'networkidle0',
            ]);
            $dom = new DOM;
            
            $dom->loadStr($page->content());
            $captchFlag = $dom->find('#hook_Block_AnonymVerifyCaptchaStart', 0);

            if ($captchFlag) {
                $this->user->blocked = true;
                $this->user->save();
                $this->setAnotherUser();
            }
        } while ($captchFlag);
        $page->goto($url, [
            "waitUntil" => 'networkidle0',
        ]);

        $coo = json_encode($page->_client->send('Network.getAllCookies'));
        
        $this->user->cookies = $coo;
        $this->user->save();
        return $page;
    }

    private function setAnotherUser()
    {
        if (OkUser::where('blocked', false)->count() === 0) {
            throw new Exception("All users are blocked");
        }
        $this->user = OkUser::where('blocked', false)->first();
    }

    private function request(array $params): bool|array
    {
        $requestResult = file_get_contents(self::BASE_URL, false, stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($params)
            )
        )));
        return json_decode($requestResult, true);
    }
}
