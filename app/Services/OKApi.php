<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\OkUser;
use App\Models\Proxy;
use Exception;
use Illuminate\Validation\Rule;
use PHPHtmlParser\Dom;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Puphpeteer\Resources\Browser;
use Nesk\Puphpeteer\Resources\Page;
use Nesk\Rialto\Data\JsFunction;

class OKApi
{
    private const BASE_URL = "https://api.ok.ru/fb.do?";
    public const TYPES = [
        'CHAT', 'CITY_NEWS', 'GROUP_MOVIE', 'GROUP_PHOTO',
        'GROUP_PRODUCT', 'GROUP_TOPIC', 'HAPPENING_TOPIC',
        'MOVIE', 'OFFER', 'PRESENT', 'SCHOOL_FORUM', 'SHARE',
        'USER_ALBUM', 'USER_FORUM', 'USER_PHOTO', 'USER_PRODUCT',
        'USER_STATUS'
    ];
    public const ACTIONS = [
        'getPostsByUser', 'getGroupFollowers',
        'getUserInfo', 'getPostInfoById', 'getPostComments',
        'getPostLikes', 'getUserAuditory', 'getPostsByGroup',
        'getFriendsByApi'
    ];

    public const TASKS_CORE_IDS = [
        'getUserInfo' => '4-1',
        'getFriendsByApi' => '3-1',
        'getGroupFollowers' => '5-1',
        'getUserSubscribers' => '1-1'
    ];

    public int $id;
    private string $appKey;
    private string $key;
    private string $secret;
    private OkUser $user;
    private JsFunction $sutoscrollFunction;
    private Puppeteer $puppeteer;
    private Browser $browser;


    public static function validationRules(): array
    {
        return [
            'getFriendsByApi' => [
                'logins' => 'required'
            ],
            'getUserSubscribers' => [
                'logins' => 'required'
            ],
            'getPostsByGroup' => [
                'url' => 'required',
                'limit' => 'required'
            ],
            'getGroupFollowers' => [
                'logins' => 'required',
            ],
            'getUserInfo' => [
                'logins' => 'required',
            ],
            'getPostInfoByUrl' => [
                'url' => 'required',
            ],
            'getPostComments' => [
                'id' => 'required',
                'limit',
            ],
            'getPostLikes' => [
                'id' => 'required',
                'limit' => 'required'
            ],
            'getUserAuditory' => [
                'user_id' => 'required',
                'limit' => 'required',
                'mode' => ['required', Rule::in([
                    'subscribers',
                    'friends',
                    'groups'
                ])]
            ],
            'getPostInfoById' => [
                'id' => 'required'
            ],
            'getPostsByUser' => [
                'url' => 'required',
                'limit' => 'required'
            ],
            'getPostUserActivity' => [
                'urls' => "required",
                'withEducation'
            ]
        ];
    }

    private function sessionBlocked($response): bool
    {
        return isset($response['error_code']) && $response['error_code'] == 102;
    }

    private function setRandomToken()
    {
        $okToken = ApiToken::inRandomOrder()->first();
        $this->appKey = $okToken->app_key;
        $this->key = $okToken->key;
        $this->secret = $okToken->secret;
    }


    public function __construct()
    {
        $this->setRandomToken();
        try {
            $this->setAnotherUser();
        } catch (Exception $exception) {
            dump($exception->getMessage());
        }
        // $this->user = OkUser::find(9);


        $this->init();
    }

    private function init()
    {
        $this->sutoscrollFunction = JsFunction::createWithBody("
        async function subscribe() {
            let response = await await new Promise(resolve => {
                    const distance = 100; // should be less than or equal to window.innerHeight
                    const delay = 100;
                    const timer = setInterval(() => {
                    document.scrollingElement.scrollBy(0, distance);
                    if (document.scrollingElement.scrollTop + window.innerHeight >= document.scrollingElement.scrollHeight) {
                        clearInterval(timer);
                        resolve();
                    }
                    }, delay);
                    });
            await subscribe();
        }
          subscribe();
        ");
    }

    /**
     * @throws Exception
     */
    public function getFriendsByApi($logins)
    {
        $logins = $this->getIdsChunks($logins, 1_000_000)[0];
        $result = [];
        foreach ($logins as $user) {

            do {
                $method = "friends.get";

                $md5 = md5("application_key=" . $this->appKey . "fid=" . $user . "format=jsonmethod=" . $method . $this->secret);

                $params = [
                    'application_key' => $this->appKey,
                    'fid' => $user,
                    'format' => 'json',
                    'method' => $method,
                    'sig' => $md5,
                    'access_token' => $this->key,
                ];

                $output = $this->request($params);
                $this->setRandomToken();
            } while (isset($output['error_code']) && $output['error_code'] == 102);

            if (isset($output['error_code']) && $output['error_code'] == 455) {
                $result[$user] = [];
            } elseif (isset($output['error_code']) && ($output['error_code'] == 300 || $output['error_code'] == 1 )) {
                continue;
            } elseif (isset($output['error_code'])) {
                throw new Exception(json_encode($output));
            } else {
                $result[$user] = array_map(function ($value) {
                    return ['id' => $value];
                }, $output);
            }
        }
        return $result;
    }

    private function httpProxyGet($url): string
    {
        do {
            $proxy = Proxy::where('blocked', false)->inRandomOrder()->first();
            if(!$proxy) {
                Proxy::query()->update(['blocked' => false]);
                $proxy = Proxy::where('blocked', false)->inRandomOrder()->first();
            }
            try {
                $auth = base64_encode($proxy->user . ':' . $proxy->password);
                $proxyUrl = 'tcp://' . $proxy->ip;
                return file_get_contents($url, false, stream_context_create(array(
                    'http' => array(
                        'proxy' => $proxyUrl,
                        'request_fulluri' => true,
                        'method' => 'GET',
                        'header' => "Proxy-Authorization: Basic $auth",
                    )
                )));
            } catch (Exception $exception) {
                // TODO add log
                $proxy->blocked = true;
                $proxy->save();
            }
        } while (true);
    }

    public function getUserSubscribers($logins)
    {
        $logins = $this->getIdsChunks($logins, 1_000_000)[0];
        $output = [];

        foreach ($logins as $id) {
            try {
                $page = 1;
                do {
                    $data = [];
                    $link = "https://m.ok.ru/dk?st.cmd=friendFriends&st.sbr=on&st.friendId=$id&st.sbs=off&st.frwd=on&st.page=$page&st.dir=FORWARD&_prevCmd=friendFriends";
                    $dom = new Dom;

                    $pageContent = $this->httpProxyGet($link);
                    dump(1);
                    $dom->loadStr($pageContent);
                    $page += 1;

                    $users = $dom->find('.item.it');
                    foreach ($users as $user) {
                        $avatar = $user->find('.common-avatar', 0)->find('img', 0)->getAttribute('src');
                        $name = $user->find('.emphased.usr', 0)->text();
                        $profile_link = $user->find('a.u-ava', 0)->getAttribute('href');
                        if (!isset($output[$id][$profile_link])) {
                            $data[$profile_link] = [
                                'name' => $name,
                                'avatar' => "https:" . $avatar,
                                'id' => $this->getUrlInfo("https://ok.ru$profile_link")['objectId'] ?? "",
                            ];
                        }
                    }
                    $output[$id] = array_merge($data, $output[$id] ?? []);
                } while (!empty($data));


            } catch (Exception $exception) {
                dump($exception->getMessage());
                // TODO log error
            }
        }
        foreach ($output as &$data) {
            $data = array_values($data);
        }
        return $output;
    }

    public function getUserAuditory($user_id, $limit, $mode)
    {
        $limitExist = $limit != "-1";
        $this->puppeteer = new Puppeteer([
            'executable_path' => config('puppeter.node_path'),
        ]);
        $this->browser = $this->puppeteer->launch([
            // 'headless' => false
        ]);
        $url = "http://ok.ru/profile/$user_id/$mode";
        dump($url);
        $page = $this->browser->newPage();
        if (!$this->user->cookies) {
            $page = $this->relogin($page, $url);
        } else {
            $cookies = json_decode($this->user->cookies, JSON_OBJECT_AS_ARRAY);
            $page->setCookie(...$cookies['cookies']);
            $page->goto($url);

            $dom = new DOM;
            $dom->loadStr($page->content());
            $flag = $dom->find('#hook_Block_ContentUnavailableForAnonymMRB', 0);
            $blockedPage = $dom->find('.hookBlock', 0);
            if ($flag || $blockedPage) {
                $page = $this->relogin($page, $url);
            }
            $flag1 = $dom->find('a#buttonCancel', 0);
            $flag = $dom->find('a.nav-side_i.__ac', 0);
            dump("START CHECKING FALGS");
            if ($flag1) {
                $page->click('a#buttonCancel');
                $page->goto($url, [
                    "waitUntil" => 'networkidle0',
                ]);
                dump("FLAG1");
            } elseif (!$flag) {
                $page->goto($url, [
                    "waitUntil" => 'networkidle0',
                ]);
                dump("FLAG2");
            }
            file_put_contents(storage_path('logs/') . 'output.html', $page->content());
        }


        $page->evaluate($this->sutoscrollFunction);

        $dom = new Dom;
        $iterations = 0;
        $output = [];
        ini_set('max_execution_time', 0);
        if ($mode == 'groups') {
            $lastArraySize = 0;
            $equalArrayCount = 0;
            do {
                $dom->loadStr($page->content());

                $postsHtml = $dom->find('.ugrid_i.show-on-hover.group-detailed-card');
                foreach ($postsHtml as $postHtml) {
                    $group_id = $postHtml->getAttribute('data-group-id');
                    $output[$group_id] = $postHtml->getAttribute('data-group-id');
                }
                if ($iterations++ > $limit && $limitExist) {
                    break;
                }
                sleep(2);
                $equalArrayCount += $lastArraySize == sizeof($output);
                $lastArraySize = sizeof($output);
                if ($equalArrayCount > 5) {
                    break;
                }
            } while (!$limitExist || sizeof($output) < $limit);
        } else {
            $lastArraySize = 0;
            $equalArrayCount = 0;
            do {
                $dom->loadStr($page->content());

                $postsHtml = $dom->find('.ugrid_i');
                foreach ($postsHtml as $postHtml) {
                    if ($mode === 'friends') {
                        $jsInfo = $postHtml->find('.photo', 0)->find('a', 0);
                        $url = $jsInfo->getAttribute('href');
                    } else {
                        $jsInfo = $postHtml->find('.user-grid-card_img', 0);
                        $url = $jsInfo->getAttribute('href');
                    }
                    $user_id = explode('/', $url);
                    $user_id = end($user_id);
                    if (is_numeric($user_id)) {
                        $output[$user_id] = $user_id;
                    }
                }
                if ($iterations++ > $limit && $limitExist) {
                    dump("BREAKET ON ITERATIONS");
                    break;
                }
                sleep(2);
                $equalArrayCount += $lastArraySize == sizeof($output);
                $lastArraySize = sizeof($output);
                if ($equalArrayCount > 5) {
                    dump("BREAKET ON ARRAY");
                    break;
                }
            } while (!$limitExist || sizeof($output) < $limit);
        }

        $this->browser->close();
        return array_values($output);
    }

    public function getUserEducation($id)
    {
        $url = "https://ok.ru/profile/$id/about";
        $page = $this->browser->newPage();
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

        $dom = new Dom;
        ini_set('max_execution_time', 0);
        $dom->loadStr($page->content());
        $infs = $dom->find('div.user-profile_i');
        $edu = [];
        foreach ($infs as $info) {
            if (
                $info->find('.svg-ic.svg-ico_globe_16.tico_img', 0)
                || $info->find('.svg-ic.svg-ico_education_16.tico_img', 0)
            ) {
                $div = $info->find('div.user-profile_i_value', 0);
                $span = $div->find('span', 0);
                $edu[] = $span->text();
            }
        }
        return $edu;
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
        $this->puppeteer = new Puppeteer([
            'executable_path' => config('puppeter.node_path'),
        ]);
        $this->browser = $this->puppeteer->launch([
            // 'headless' => false
        ]);
        $page = $this->browser->newPage();
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

        $page->evaluate($this->sutoscrollFunction);

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

        $this->browser->close();
        return array_values($posts);
    }

    public function getPostsByUser($url, $limit)
    {
        $this->puppeteer = new Puppeteer([
            'executable_path' => config('puppeter.node_path'),
        ]);
        $this->browser = $this->puppeteer->launch([
            // 'headless' => false
        ]);
        $page = $this->browser->newPage();
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

        $page->evaluate($this->sutoscrollFunction);

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
        $this->browser->close();

        return array_values($posts);
    }

    public function getPostUserActivity(string|array $urls, $withEducation = false)
    {
//        $this->puppeteer = new Puppeteer([
//            'executable_path' => config('puppeter.node_path'),
//        ]);
//        $this->browser = $this->puppeteer->launch([
//            //  'headless' => false
//        ]);
        if (is_string($urls)) {
            $urls = [$urls];
        }
        // return $urls;
        $output = [];
        foreach ($urls as $url) {
            $data = explode(';', $url);

            try {
                $ibd = $data[0];
                $url = explode('?', $data[1])[0];
            } catch (Exception $exception) {
                // dump($exception->getMessage());
            }
            if(str_contains($url, '?')) {
                $url = explode('?', $url)[0];
            }
            $ibd = 1;

            $postInfo = $this->getPostInfoByUrl($url);
            if (!isset($postInfo[0]['discussion'])) {
                continue;
            }
            $postId = $postInfo[0]['discussion']['object_id'];
            $comments = $this->getPostComments($postId, -1);

            $users = [];
            $userIds = [];
            $userAddictionsInfo = [];
            foreach ($comments as $comment) {
                $userId = $comment['author_id'];
                $userIds[] = $userId;
                if (sizeof($userIds) >= 99) {
                    $userAddictionsInfo = array_merge($userAddictionsInfo, $this->getUserInfo($userIds));
                    $userIds = [];
                }
                $users[$userId . 'comment' . $postId] = [
                    'postId' => $postId,
                    'activityType' => 'comment',
                    'profileId' => $userId,
                    'profileUrl' => "https://ok.ru/profile/$userId",
                    'commentText' => $comment['text'],
                    'ibd' => $ibd
                ];
            }

            $likes = $this->getPostLikes($postId, -1);

            foreach ($likes as $like) {
                $userId = $like['uid'];
                $userIds[] = $userId;
                if (sizeof($userIds) >= 99) {
                    $userAddictionsInfo = array_merge($userAddictionsInfo, $this->getUserInfo($userIds));
                    $userIds = [];
                }
                $users[$userId . 'like' . $postId] = [
                    'postId' => $postId,
                    'activityType' => 'like',
                    'profileId' => $userId,
                    'profileUrl' => "https://ok.ru/profile/$userId",
                    'commentText' => '',
                    'ibd' => $ibd
                ];
            }

            if (sizeof($userIds) > 0) {
                $userAddictionsInfo = array_merge($userAddictionsInfo, $this->getUserInfo($userIds));
            }
            // dd($userAddictionsInfo);
            foreach ($userAddictionsInfo as $userId => $userInfo) {
                $userInfo = $userInfo[0];
                $userId = $userInfo['uid'];
                $edu = [];
                if ($withEducation) {
                    $edu = $this->getUserEducation($userId);
                }
                $sex = $userInfo['gender'] == 'male' ? "Мужчина" : "Женщина";
                if (isset($users[$userId . 'like' . $postId])) {
                    $users[$userId . 'like' . $postId] = array_merge($users[$userId . 'like' . $postId], [
                        'education' => implode(',', $edu),
                        'gender' => $sex,
                        'age' => $userInfo['age'] ?? '',
                        'location' => $userInfo['location'],
                        'name' => $userInfo['name'],
                        'postUrl' => $url,
                        'created_at' => now()
                    ]);
                }
                if (isset($users[$userId . 'comment' . $postId])) {
                    $users[$userId . 'comment' . $postId] = array_merge($users[$userId . 'comment' . $postId], [
                        'education' => implode(',', $edu),
                        'gender' => $sex,
                        'age' => $userInfo['age'] ?? '',
                        'location' => $userInfo['location'],
                        'name' => $userInfo['name'],
                        'postUrl' => $url,
                        'created_at' => now()
                    ]);
                }
            }
            $output = array_merge($output, array_values($users));
        }
        return $output;
    }

    public function getUrlInfo($url): array
    {
        do {
            $method = "url.getInfo";
            $md5 = md5("application_key=" . $this->appKey . "format=jsonmethod=" . $method . "url=" . $url . $this->secret);

            $params = [
                'application_key' => $this->appKey,
                'format' => 'json',
                'method' => $method,
                'url' => $url,
                'sig' => $md5,
                'access_token' => $this->key,
            ];

            $response = $this->request($params);
            if ($this->sessionBlocked($response)) {
                $this->setRandomToken();
            }
        } while ($this->sessionBlocked($response));

        if (!is_array($response)) {
            return [];
        }
        return $response;
    }

    public function getGroupFollowers($logins): bool|array
    {
        $output = [];
        foreach ($logins as $link) {
            $anchor = "";
            $anpr = "";

            $id = $link;
            $hasMore = false;
            if (is_string($link)) {
                $id = $this->getUrlInfo($link)['objectId'];
            }
            do {

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

                $response = $this->request($params);
                // If session blocked - reset session
                if ($this->sessionBlocked($response)) {
                    $this->setRandomToken();
                } else {
                    // then if we have more subscribers, repeat loop until it not saved
                    $hasMore = $response['has_more'] ?? false;
                    $anchor = $response['anchor'] ?? "";

                    // save users
                    $users = $response['members'] ?? [];
                    foreach ($users as &$user) {
                        // change object on userId only
                        $user = ['user_id' => $user['userId']];
                    }
                    $output[$id] = array_merge($users, $output[$id] ?? []);
                }
            } while ($this->sessionBlocked($response) || $hasMore);
        }
        return $output;
    }

    private function getIdsChunks($logins, $size): array
    {
        $ids = $logins;
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        foreach ($ids as &$id) {
            if (is_string($id)) {
                $tmp = explode('/', $id);
                $id = end($tmp);
            }
        }

        return array_chunk($ids, $size);
    }

    public function getUserInfo(array|int $logins): bool|array
    {
        $ids_array = $this->getIdsChunks($logins, 99);
        dump($ids_array);
        $output = [];
        $maxArray = [];
        foreach ($ids_array as $ids) {

            $newIds = [];
            foreach ($ids as $id) {
                if (is_string($id)) {
                    $result = $this->getUrlInfo("https://ok.ru/profile/$id");
                    if (!$result['objectId']) {
                        continue;
                    }
                    $newIds[] = $result['objectId'];
                } else {
                    $newIds[] = $id;
                }
            }
            $ids = $newIds;

            if (is_array($ids)) {
                $ids = implode(',', $ids);
            }
            do {
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
                $response = $this->request($params);
                if (isset($response['error_code']) && $response['error_code'] == 102) {
                    $this->setRandomToken();
                }
            } while (isset($response['error_code']) && $response['error_code'] == 102);

            if (!isset($response['error_code'])) {
                foreach ($response as &$datum) {
                    try {
                        $datum['location'] = $datum['location']['city'] . '-' . $datum['location']['country'] . '-' . $datum['location']['countryCode'] . '-' . $datum['location']['countryName'];
                    } catch (Exception $exception) {
                        $datum['location'] = '';
                    }
                    if (sizeof($datum) > sizeof($maxArray)) {
                        $maxArray = $datum;
                    }
                    $output[$datum['uid']] = [$datum];
                }
            }
        }
        foreach ($output as $userId => &$userArr) {
            foreach (array_keys($maxArray) as $key) {
                $userArr[0][$key] = $userArr[0][$key] ?? null;
            }
        }
        return $output;
    }

    public function getPostInfoById($id): array|bool
    {
        $output = [];
        foreach (self::TYPES as $type) {
            do {
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
                if ($this->sessionBlocked($result)) {
                    $this->setRandomToken();
                }
            } while ($this->sessionBlocked($result));
            if (!empty($result)) {
                $output[] = $result;
            }
        }
        return $output;
    }


    public function getPostInfoByUrl($url): array|bool
    {
        $array = explode('/', $url);
        $id = end($array);
        return $this->getPostInfoById($id);
    }

    public function getPostComments($id, $limit): bool|array
    {
        $offset = 0;
        $answer = [];
        $limitExist = $limit != -1;
        $comments = [];
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
            $answer = $this->request($params);
            if (isset($answer['commentss']) && sizeof($answer['commentss']) > 0) {
                $comments = $answer['commentss'];
                while (!$limitExist || sizeof($comments) < $limit) {
                    $offset += sizeof($comments);
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
                    $answer = $this->request($params);
                    if (sizeof($answer['commentss']) === 0) {
                        break;
                    }
                    $comments = array_merge($comments, $answer['commentss']);
                }
                return $comments;
            }
        }

        return [];
    }

    public function getPostLikes($id, $limit): bool|array
    {
        $limitExist = $limit != -1;
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
                while (!$limitExist || sizeof($output) < $limit) {
                    $anchor = $answer['anchor'];
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
                        'access_token' => $this->key,
                        'anchor' => $anchor
                    ];
                    // dump($md5);
                    // $params = array_merge(array('anchor' => $anchor), $params);

                    $answer = $this->request($params);

                    if (!empty($answer) && !array_key_exists('error_msg', $answer) && isset($answer['users'])) {
                        $output = array_merge($output, $answer['users']);
                        $anchor = $answer['anchor'];
                        if (empty($answer['users'])) {
                            return $output;
                        }
                    } else {
                        return $output;
                    }
                }
                return $output;
            }
        }
        return [];
    }

    private function relogin($page, $url): Page
    {
        do {
            do {
                $page->goto('https://ok.ru/dk?st.cmd=anonymMain', [
                    "waitUntil" => 'networkidle0',
                ]);
                $dom = new DOM;
                $dom->loadStr($page->content());
                $link = $dom->find('#anonymPageContent > div > div.anon-main-design21_central-panel > div.clearfix.js-tab-login-form.anon-main-design21_tab-login > div > div.tab-filter-with-body > div:nth-child(1) > div > a.js-login-login', 0);
                if ($link) {
                    $page->click('#anonymPageContent > div > div.anon-main-design21_central-panel > div.clearfix.js-tab-login-form.anon-main-design21_tab-login > div > div.tab-filter-with-body > div:nth-child(1) > div > a.js-login-login');
                }
                $email_field = $dom->find('#field_email', 0);
                $password_field = $dom->find('#field_password', 0);
            } while (!$email_field && !$password_field);

            $page->type('#field_email', $this->user->login);
            $page->type('#field_password', $this->user->password);

            $page->click('input[type="submit"]');

            $page->waitForNavigation([
                "waitUntil" => 'networkidle0',
            ]);
            $dom = new DOM;

            $dom->loadStr($page->content());
            $captchFlag = $dom->find('#hook_Block_AnonymVerifyCaptchaStart', 0);
            $blockedFlag = $dom->find('#hook_Block_AnonymUnblockConfirmPhone', 0);

            dump("ACTIVE BLOCK CHECK");
            if ($captchFlag || $blockedFlag) {
                dump("THIS USER IS BLOCKED");
                $this->user->blocked = true;
                $this->user->save();
                $this->setAnotherUser();
            }
        } while ($captchFlag || $blockedFlag);
        $page->goto($url, [
            "waitUntil" => 'networkidle0',
        ]);

        $dom = new DOM;
        $dom->loadStr($page->content());
        $flag = $dom->find('a.nav-side_i.__ac', 0);
        if (!$flag) {
            $page->goto($url, [
                "waitUntil" => 'networkidle0',
            ]);
        }

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
        do {
            $proxy = Proxy::where('blocked', false)->inRandomOrder()->first();
            if (!$proxy) {
                Proxy::query()->update(['blocked', false]);
                $proxy = Proxy::where('blocked', false)->inRandomOrder()->first();
            }
            try {
                $auth = base64_encode($proxy->user . ':' . $proxy->password);
                $proxyUrl = 'tcp://' . $proxy->ip;
                $requestResult = file_get_contents(self::BASE_URL, false, stream_context_create(array(
                    'http' => array(
                        'proxy' => $proxyUrl,
                        'request_fulluri' => true,
                        'method' => 'POST',
                        'header' => "Proxy-Authorization: Basic $auth\r\nContent-type: application/x-www-form-urlencoded\r\n",
                        'content' => http_build_query($params)
                    )
                )));
                return json_decode($requestResult, true);
            } catch (Exception $exception) {
                // TODO add log
                $proxy->blocked = true;
                $proxy->save();
            }
        } while (true);
    }
}
