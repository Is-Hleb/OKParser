<?php

namespace App\Http\Controllers;

use App\Models\OkUser;
use PHPHtmlParser\Dom;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Puphpeteer\Resources\Browser;
use Nesk\Puphpeteer\Resources\Page;
use Nesk\Rialto\Data\JsFunction;

class TestController extends Controller
{
    protected OkUser $user;
    private JsFunction $sutoscrollFunction;
    private Puppeteer $puppeteer;
    private Browser $browser;

    public function __construct()
    {
        $this->puppeteer = new Puppeteer([
            'executable_path' => config('puppeter.node_path'),
        ]);
        $this->browser = $this->puppeteer->launch([
            'headless' => false
        ]);
        $this->user = OkUser::where('blocked', false)->first();
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

    public function relogin($page, $url) : Page {
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
        $limit = 10;
        $url = "https://ok.ru/ok";
        $user_id = "203475530776";
        $url = "http://ok.ru/profile/$user_id/groups";
    

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
        do {
            $dom->loadStr($page->content());
            
            $postsHtml = $dom->find('.ugrid_i');
            foreach($postsHtml as $postHtml) {
                $jsInfo = $postHtml->find('.user-grid-card_img', 0);
                $url = $jsInfo->getAttribute('href');
                $user_id = explode('/', $url);
                $user_id = end($user_id);
                if (is_numeric($user_id)) {
                    $output[] = $user_id;
                }
            }
            if($iterations++ > $limit) {
                break;
            }
            sleep(2);
        } while(sizeof($output) < $limit);

        $this->browser->close();
        dump($output);        
    }
}
