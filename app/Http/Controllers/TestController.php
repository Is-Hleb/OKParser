<?php

namespace App\Http\Controllers;

use App\Models\OkUser;
use PHPHtmlParser\Dom;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Puphpeteer\Resources\Page;
use Nesk\Rialto\Data\JsFunction;

class TestController extends Controller
{
    protected OkUser $user;
    public function __construct()
    {
        $this->user = OkUser::where('blocked', false)->first();
    }

    public function relogin($page, $url) : Page {
        do  {
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

            if($captchFlag) {
                $this->user->blocked = true;
                $this->user->save();
                $this->setAnotherUser();
            }
        } while($captchFlag);
        $page->goto($url, [
            "waitUntil" => 'networkidle0',
        ]);

        $coo = json_encode($page->_client->send('Network.getAllCookies'));
        
        $this->user->cookies = $coo;
        $this->user->save();
        return $page;
    }

    public function setAnotherUser() {
        if(OkUser::where('blocked', false)->count() === 0) {
            throw new Exception("All users are blocked");
        }
        $this->user = OkUser::where('blocked', false)->first();
    }


    public function __invoke()
    {
        $postsCount = 10;
        $url = "https://ok.ru/ok";
        
        $puppeteer = new Puppeteer([
            'executable_path' => config('puppeter.node_path'),
        ]);
        $browser = $puppeteer->launch([
            'headless' => false
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
            if($mustLogin) {
                $page = $this->relogin($page, $url);
            }
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
            $loadMore = $dom->find('a.js-show-more.link-show-more', 0);
            $loadMoreContainer = $dom->find('div.loader-container', 0);

            if($loadMore && !$loadMoreContainer) {
                $page->click('a.js-show-more.link-show-more');
            }
            $postsHtml = $dom->find('.feed-w');
            foreach($postsHtml as $postHtml) {
                $jsInfo = $postHtml->find('.feed_cnt', 0);
                $info = explode(',', $jsInfo->getAttribute('data-l'));
                dump($info);
                if(sizeof($info) === 4) {
                    $info = [
                        'topicId' => $info[1],
                        'groupId' => $info[3]
                    ];
                    $posts[$info['topicId']] = $info['topicId'];
                }
            }
            if($iterations++ > $postsCount) {
                break;
            }
            sleep(2);
        } while(sizeof($posts) < $postsCount);

        $browser->close();
        dump($posts);        
    }
}
