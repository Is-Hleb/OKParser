<?php

namespace App\Http\Controllers;

use App\Models\OkUser;
use PHPHtmlParser\Dom;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Puphpeteer\Resources\Browser;
use Nesk\Puphpeteer\Resources\Page;
use Nesk\Rialto\Data\JsFunction;
use App\Models\ApiToken;
use Exception;

use function PHPSTORM_META\map;

class TestController extends Controller
{
    private const BASE_URL = "https://api.ok.ru/fb.do?";

    protected OkUser $user;
    private JsFunction $sutoscrollFunction;
    private Puppeteer $puppeteer;
    private Browser $browser;
    private string $appKey;
    private string $key;
    private string $secret;

    public const TYPES = [
        'CHAT','CITY_NEWS','GROUP_MOVIE', 'GROUP_PHOTO',
        'GROUP_PRODUCT','GROUP_TOPIC','HAPPENING_TOPIC',
        'MOVIE','OFFER','PRESENT','SCHOOL_FORUM','SHARE',
        'USER_ALBUM','USER_FORUM','USER_PHOTO','USER_PRODUCT',
        'USER_STATUS'
    ];


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

    public function __construct()
    {
        $this->puppeteer = new Puppeteer([
            'executable_path' => config('puppeter.node_path'),
        ]);
        $this->browser = $this->puppeteer->launch([
           //  'headless' => false
        ]);
        $this->user = OkUser::where('blocked', false)->first();
        $okToken = ApiToken::find(8);
        $this->setAnotherUser();

        $this->appKey = $okToken->app_key;
        $this->key = $okToken->key;
        $this->secret = $okToken->secret;
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

    public function relogin($page, $url) : Page
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

    public function setAnotherUser()
    {
        if (OkUser::where('blocked', false)->count() === 0) {
            throw new Exception("All users are blocked");
        }
        $this->user = OkUser::where('blocked', false)->first();
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

    public function getPostInfoByUrl($url): array|bool
    {
        $array = explode('/', $url);
        $id = end($array);
        return $this->getPostInfoById($id);
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

    public function __invoke()
    {
        $postUrl = "https://ok.ru/profile/514677397371/album/836850790267/884627920251";
        $postInfo = $this->getPostInfoByUrl($postUrl);

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
            ];
        }

        if (sizeof($userIds) > 0) {
            $userAddictionsInfo = array_merge($userAddictionsInfo, $this->getUserInfo($userIds));
            $userIds = [];
        }
        
        foreach ($userAddictionsInfo as $userInfo) {
            $userId = $userInfo['uid'];
            $edu = [];
            // $edu = $this->getUserEducation($userId);
            $sex = $userInfo['gender'] == 'male' ? "Мужчина" : "Женщина";
            if(isset($users[$userId . 'like' . $postId])) {
                $users[$userId . 'like' . $postId] = array_merge($users[$userId . 'like' . $postId], [
                    'education' => implode(',', $edu),
                    'gender' => $sex,
                    'age' => $userInfo['age'] ?? '',
                    'location' => implode(',', array_values($userInfo['location']))
                ]);
            }
            if(isset($users[$userId . 'comment' . $postId])) {
                $users[$userId . 'comment' . $postId] = array_merge($users[$userId . 'comment' . $postId], [
                    'education' => implode(',', $edu),
                    'gender' => $sex,
                    'age' => $userInfo['age'] ?? '',
                    'location' => implode(',', array_values($userInfo['location']))
                ]);
            }
        }
        
        return array_values($users);
    }
}
