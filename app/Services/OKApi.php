<?php

namespace App\Services;

use Illuminate\Validation\Rule;
use JetBrains\PhpStorm\ArrayShape;
use RoachPHP\Roach;
use App\Spiders\OkSubscribers;
use RoachPHP\Spider\Configuration\Overrides;

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
        'getFollowers',
        'getUserInfo',
        'getPost',
        'getComments',
        'getLikes',
        'getSubscribersIds'
    ];

    #[ArrayShape(['getFollowers' => "string[]", 'getUserInfo' => "string[]", 'getPost' => "array", 'getComments' => "array", 'getLikes' => "array"])] public static function validationRules(): array
    {
        return [
            'getFollowers' => [
                'id' => 'required',
                'anchor'
            ],
            'getUserInfo' => [
                'ids' => 'required',
            ],
            'getPost' => [
                'id' => 'required',
                'type' => [
                    'required',
                    Rule::in(self::TYPES)
                ]
            ],
            'getComments' => [
                'id' => 'required',
                'type' => [
                    'required',
                    Rule::in(self::TYPES)
                ]
            ],
            'getLikes' => [
                'id' => 'required',
                'type' => [
                    'required',
                    Rule::in(self::TYPES)
                ]
            ],
            'getSubscribersIds' => [
                'user_id' => 'required'
            ]
        ];
    }

    public int $id;
    private string $appKey = "CGPDPPJGDIHBABABA";
    private string $key = "tkn1o2oL049LyxGPHFOHvrJdviNOd2ez5mOrgnmNJRLVv9Jnu5qQaQlA9PSFX6MnGDDKn";
    private string $secret = "702d0e9c2f9e89189efd3a87d95901a0";

    public function getSubscribersIds($user_id) 
    {
        $url = OkSubscribers::getInitialUrl($user_id, 1);
        return Roach::collectSpider(
            OkSubscribers::class,
            new Overrides([$url]),
            context: ['user_id' => $user_id]
        );
    }

    public function rules(): array
    {
        return [
            // name, email, subject and body are required
            [['id'], 'required'],
        ];
    }

    public function getFollowers($id, $anchor = ""): bool|array
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

    /**
     * @param $id
     * @param $type [
     *
     * ]
     *
     * @return array|bool
     */
    public function getPost($id, $type): array|bool
    {
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

        return $this->request($params);
    }

    public function getComments($id, $type, $offset = 0): bool|array
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

    public function getLikes($id, $type, $anchor = ""): bool|array
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
