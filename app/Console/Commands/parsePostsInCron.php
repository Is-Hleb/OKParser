<?php

namespace App\Console\Commands;

use App\Models\Link;
use App\Models\TaskE;
use App\Services\CoreApiService;
use App\Services\IriApi;
use App\Services\OKApi;
use Carbon\Carbon;
use Illuminate\Console\Command;

class parsePostsInCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:e';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(IriApi $iriApi, OKApi $api)
    {
        $links = $iriApi->getAllLinks();

        foreach ($links as $link) {
            if(!Link::where('link', $link[1])->count()) {
                if(str_contains($link[1], "ok.ru")) {
                    Link::create([
                        'data' => ["ibd" => $link[0]],
                        'link' => $link[1],
                        'task_key' => "task_e"
                    ]);
                }
            }
        }
        $links = Link::all()->toArray();
        $linksChunks = array_chunk($links, 10);

        foreach ($linksChunks as $links) {
            try {
                foreach (array_reverse($links) as &$link) {
                    $activities = $api->getPostUserActivity($link['link']);
                    if(empty($activities)) {
                        dump("Activities is empty");
                        continue;
                    }
                    $lastActivities = TaskE::where('is_vk', false)
                        ->whereDate('created_at', '<=', Carbon::today())
                        ->where("postId", $activities[0]['postId'])
                        ->get();

                    if(!$lastActivities->count()) {
                        TaskE::insert($activities);
                    } else {
                        $newActivities = [];
                        if($lastActivities->count < sizeof($activities)) {
                            TaskE::insert(array_slice($activities, $lastActivities->count - 1));
                        }
                        foreach ($activities as $activity) {
                            $trig = false;
                            foreach ($lastActivities as $lastActivity) {
                                if($activity['profileId'] != $lastActivity->profileId || $activity['commentText'] != $lastActivity->commentText) {
                                    $trig = true;
                                    break;
                                }
                            }
                            if($trig) {
                                $newActivities[] = $activity;
                            }
                        }
                        TaskE::insert($newActivities);
                    }
                }
            } catch(\Exception $exception) {
                dump($exception->getMessage());
            }
        }
        return 0;
    }
}
