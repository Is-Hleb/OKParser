<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\OkParserApi;
use App\Models\CronTaskinfo;
use App\Models\JobInfo;
use Illuminate\Http\Request;

class CronController extends Controller
{
    public function show()
    {
        return view('web.cron', [
            'cronTabs' => CronTaskinfo::all()
        ]);
    }

    public function postOutput()
    {

    }

    public function stopCron($id)
    {
        $cronTaskinfo = CronTaskinfo::find($id);
        $cronTaskinfo->status = JobInfo::FINISHED;
        $cronTaskinfo->save();
        return redirect()->back();
    }

    public function postLinks(Request $request)
    {

        $content = file_get_contents($request->file('csv')->getRealPath());
        $items = explode("\n", $content);
        $items = array_map(function($item){
            return str_replace("\r", '', $item);
        }, $items);
        $input = array_chunk($items, 10);

        foreach ($input as $items) {
            $jobInfo = new JobInfo([
                'status' => JobInfo::WAITING
            ]);
            $jobInfo->save();

            $cronInfo = new CronTaskinfo([
                'method' => 'getPostUserActivity',
                'signature' => ['urls' => $items],
                'job_info_id' => $jobInfo->id,
                'status' => JobInfo::WAITING
            ]);
             dispatch((new OkParserApi('getPostUserActivity', ['urls' => $items], $jobInfo, null)));

            $cronInfo->save();
        }
        return redirect()->back();
    }
}
