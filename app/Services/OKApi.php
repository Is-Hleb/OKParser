<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\OkUser;
use Illuminate\Validation\Rule;
use RoachPHP\Roach;
use App\Spiders\OkSubscribers;
use Illuminate\Support\Facades\DB;
use RoachPHP\Spider\Configuration\Overrides;
use PHPHtmlParser\Dom;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Puphpeteer\Resources\Page;
use Nesk\Rialto\Data\JsFunction;

/**
 * ContactForm is the model behind the contact form.
 */
class OKApi
{
    private const BASE_URL = "https://api.ok.ru/fb.do?";
    public const TYPES = [
        'CHAT',
        'CITY_NEWS',
        'GROUP_MOVIE',
        'GROUP_PHOTO',
        'GROUP_PRODUCT',
        'GROUP_TOPIC',
        'HAPPENING_TOPIC',
        'MOVIE',
        'OFFER',
        'PRESENT',
        'SCHOOL_FORUM',
        'SHARE',
        'USER_ALBUM',
        'USER_FORUM',
        'USER_PHOTO',
        'USER_PRODUCT',
        'USER_STATUS',
    ];
    public const ACTIONS = [
        'getPostsByUser',
        'getGroupFollowers',
        'getUserInfo',
        'getPostinfoById',
        'getPostComments',
        'getPostLikes',
        'getSubscribersIds'
    ];

    public static function validationRules(): array
    {
        return [
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
                'type' => [
                    'required',
                    Rule::in(self::TYPES)
                ]
            ],
            'getPostLikes' => [
                'id' => 'required',
                'type' => [
                    'required',
                    Rule::in(self::TYPES)
                ]
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

    public int $id;
    private string $appKey = "CGPDPPJGDIHBABABA";
    private string $key = "tkn1o2oL049LyxGPHFOHvrJdviNOd2ez5mOrgnmNJRLVv9Jnu5qQaQlA9PSFX6MnGDDKn";
    private string $secret = "702d0e9c2f9e89189efd3a87d95901a0";
    private OkUser $user;

    public function relogin($page, $url) : Page {
        
        $page->goto('https://ok.ru');
        sleep(1);
        $page->type('#field_email', $this->user->login);
        $page->type('#field_password', $this->user->password);

        $page->click('input[type="submit"]');

        $page->waitForNavigation();
        $dom = new DOM;
        $dom->loadStr($page->content());
        $captchFlag = $dom->find('#hook_Block_AnonymVerifyCaptchaStart', 0);

        if($captchFlag) {
            $this->user->blocked = true;
            $this->user->save();
            $this->setAnotherUser();
            $page = $this->relogin($page, $url);
        }

        $page->goto($url);

        $coo = json_encode($page->_client->send('Network.getAllCookies'));
        
        $this->user->cookies = $coo;
        $this->user->save();
        return $page;
    }

    public function setAnotherUser() {
        $this->user = OkUser::inRandomOrder()->first();
    }

    public function __construct()
    {
        $okToken = ApiToken::inRandomOrder()->first();
        $this->user = OkUser::inRandomOrder()->first();

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
            $page->goto($url);

            $dom = new DOM;
            $dom->loadStr($page->content());
            $flag = $dom->find('#hook_Block_ContentUnavailableForAnonymMRB', 0);
            if($flag) {
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

            $postsHtml = $dom->find('.feed-w');
            foreach($postsHtml as $postHtml) {
                $jsInfo = $postHtml->find('.feed_cnt', 0);
                $info = explode(',', $jsInfo->getAttribute('data-l'));
                if(sizeof($info) === 4) {
                    $info = [
                        'ownerUserId' => $info[1],
                        'topicId' => $info[3]
                    ];
                    if(strlen($info['ownerUserId']) !== 12) continue;
                    $posts[$info['topicId']] = $info['topicId'];
                }
            }
            if($iterations++ > $limit) {
                break;
            }
            sleep(2);
        } while(sizeof($posts) < $limit);
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
        $users = DB::connection('parser')->table('users')->whereIn('social_id', $ids)->get();
        if ($users) {
            return $users->toArray();
        }
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
            if(!empty($result)) {
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

    public function getPostComments($id, $type, $offset = 0): bool|array
    {
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

        return $this->request($params);
    }

    public function getPostLikes($id, $type, $anchor = ""): bool|array
    {
        $anpr = "";

        if ($anchor != "") {
            $anpr = "anchor=" . $anchor;
        }

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

        if ($anchor != "") {
            $params = array_merge(array('anchor' => $anchor), $params);
        }

        return $this->request($params);
    }

    protected function request(array $params): bool|array
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
