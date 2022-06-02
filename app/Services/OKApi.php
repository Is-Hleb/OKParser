<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\OkUser;
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
        'CHAT','CITY_NEWS','GROUP_MOVIE', 'GROUP_PHOTO',
        'GROUP_PRODUCT','GROUP_TOPIC','HAPPENING_TOPIC',
        'MOVIE','OFFER','PRESENT','SCHOOL_FORUM','SHARE',
        'USER_ALBUM','USER_FORUM','USER_PHOTO','USER_PRODUCT',
        'USER_STATUS',
    ];
    public const ACTIONS = [
        'getPostsByUser','getGroupFollowers',
        'getUserInfo','getPostInfoById','getPostComments',
        'getPostLikes','getUserAuditory', 'getPostsByGroup'
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

    public function __construct()
    {
        $okToken = ApiToken::inRandomOrder()->first();
        $this->setAnotherUser();

        $this->appKey = $okToken->app_key;
        $this->key = $okToken->key;
        $this->secret = $okToken->secret;
        $this->init();
    }

    private function init() {
        $this->sutoscrollFunction = JsFunction::createWithBody("
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
        ");
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
            if($flag) {
                $page = $this->relogin($page, $url);
            }
        }

        $page->evaluate($this->sutoscrollFunction);
        
        $dom = new Dom;
        $iterations = 0;
        $output = [];
        ini_set('max_execution_time', 0);
        if($mode == 'groups') {
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
                if($equalArrayCount > 5) {
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
                if($equalArrayCount > 5) {
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
        foreach($infs as $info) {
            if(
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
        $this->puppeteer = new Puppeteer([
            'executable_path' => config('puppeter.node_path'),
        ]);
        $this->browser = $this->puppeteer->launch([
           //  'headless' => false
        ]);
        if(is_string($urls)) {
            $urls = [$urls];
        }
        // return $urls;
        $output = [];
        foreach ($urls as $url) {
            $postInfo = $this->getPostInfoByUrl($url);

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
                $users[$userId . 'comment'.$postId] = [
                        'postId' => $postId,
                        'activityType' => 'comment',
                        'profileId' => $userId,
                        'profileUrl' => "https://ok.ru/profile/$userId",
                        'commentText' => $comment['text']
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
                    'commentText' => ''
                ];
            }

            if (sizeof($userIds) > 0) {
                $userAddictionsInfo = array_merge($userAddictionsInfo, $this->getUserInfo($userIds));
                $userIds = [];
            }
        
            foreach ($userAddictionsInfo as $userInfo) {
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
                            'location' => implode(',', array_values($userInfo['location']))
                    ]);
                }
                if (isset($users[$userId . 'comment' . $postId])) {
                    $users[$userId . 'comment' . $postId] = array_merge($users[$userId . 'comment' . $postId], [
                        'education' => implode(',', $edu),
                        'gender' => $sex,
                        'age' => $userInfo['age'] ?? '',
                        'location' => implode(',', array_values($userInfo['location']))
                    ]);
                }
            }
            $output = array_merge($output, array_values($users));
        }
        $file = fopen(public_path() . 'output.csv', 'w');
        $data = array_values($output);
        foreach($data as $datum) {
            fputcsv($file, $datum);
        }
        fclose($file);
        return $output;
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
