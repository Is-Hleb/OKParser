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
        $this->user = OkUser::find(5);
    }

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
        // dump(OkUser::where('blocked', false)->get());
        // dump(OkUser::where('blocked', false)->inRandomOrder()->first());
        $this->user = OkUser::inRandomOrder()->first();
    }

    public function __invoke()
    {
        $postsCount = 10;
        $url = "https://ok.ru/profile/571774735285";
        
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
                    $posts[$info['ownerUserId']][] = $info['topicId'];
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
