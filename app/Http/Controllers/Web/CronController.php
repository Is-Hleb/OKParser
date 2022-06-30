<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\OkParserApi;
use App\Models\CronTaskinfo;
use App\Models\JobInfo;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CronController extends Controller
{
    public function show(Request $request)
    {
        if ($request->get('js')) {
            return view('sections.cron', [
                'cronTabs' => CronTaskinfo::paginate(5)
            ]);
        } else {
            return view('web.cron', [
                'cronTabs' => CronTaskinfo::paginate(5)
            ]);
        }
    }

    private function getResultDelta($result): bool|array
    {
        $results = array_values($result);

        $index = 1;
        do {

            $cur = $results[sizeof($results) - $index++] ?? null;
            $last = $results[sizeof($results) - $index] ?? null;

        } while ($cur && $last && sizeof($cur) == sizeof($last));

        if (!$cur || !$last) {
            return false;
        }
        $offset = max(sizeof($cur), sizeof($last)) - 2;
        return array_slice($last, $offset);
    }

    public function postOutput(string $mode, int $id)
    {
        $cronTab = CronTaskinfo::find($id);
        switch ($mode) {
            case "last":
                $file_path = storage_path($cronTab->id . '.csv');
                $file = fopen($file_path, 'w');

                fputcsv($file, array_keys(array_values($cronTab->output)[0]));
                foreach ($cronTab->output as $date => $data) {
                    foreach ($data as $item)
                        fputcsv($file, $item);
                }
                fclose($file);
                return \response()->download($file_path);

            case "tab":
            case "delta":
                $result = $cronTab->output;
                if (!$result) {
                    return redirect()->back();
                }
                $dirPath = storage_path("cron_{$cronTab->id}");
                if (!is_dir($dirPath)) {
                    mkdir($dirPath);
                }
                if ($mode != 'delta') {
                    foreach ($result as $date => $output) {
                        $file_path = $dirPath . "/cron_{$cronTab->id}_result_$date.csv";
                        $file = fopen($file_path, 'w');
                        foreach ($output as $data) {
                            fputcsv($file, $data);
                        }
                        fclose($file);
                    }
                } else {

                    $content = $this->getResultDelta($result);

                    if (!$content) {
                        return redirect()->back();
                    }

                    $file_path = $dirPath . "/cron_{$cronTab->id}_$mode.csv";
                    $file = fopen($file_path, 'w');
                    fputcsv($file, array_keys($content[0]));
                    foreach ($content as $data) {
                        fputcsv($file, $data);
                    }
                    fclose($file);

                    return \response()->download($file_path);
                }
                $zip_path = "cron_{$cronTab->id}_results.zip";
                $zip = new \ZipArchive;
                if ($zip->open(storage_path($zip_path), \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                    $files = File::files($dirPath);

                    foreach ($files as $key => $value) {
                        $relativeNameInZipFile = basename($value);
                        $zip->addFile($value, $relativeNameInZipFile);
                    }
                    $zip->close();
                    return response()->download(storage_path($zip_path));
                }
                break;
            case "exception":

                break;
            case "group":
            case "lastGroup":
                $tabs = CronTaskinfo::where('name', $cronTab->name)->get();
                $content = [];
                if($mode == 'group') {
                    foreach ($tabs as $tab) {
                        $temp = $this->getResultDelta($tab->output ?? []);
                        if ($temp) {
                            $content = array_merge($temp, $content);
                        }
                    }

                    $file_path = storage_path("{$cronTab->name}_delta.csv");
                } else {
                    $file_path = storage_path("{$cronTab->name}_content.csv");
                    $biggestArr = [];
                    foreach ($tabs as $tab) {
                        if($tab->output) {
                            $tmp = array_values($tab->output);
                            $tmp = $tmp[sizeof($tmp) - 1];
                            foreach ($tmp as $item) {
                                if(sizeof($biggestArr) < sizeof($item)) {
                                    $biggestArr = $item;
                                }
                            }
                            $content = array_merge($content, $tmp);
                        }
                    }
                    foreach ($content as &$item) {
                        foreach ($biggestArr as $key => $value) {
                            if(!isset($item[$key])) {
                                $item[$key] = "none";
                            }
                        }
                    }
                }

                if (!$content) {
                    return redirect()->back();
                }

                $file = fopen($file_path, "w");

                fputcsv($file, array_keys($content[0]));
                foreach ($content as $data) {
                    fputcsv($file, $data);
                }
                fclose($file);
                return \response()->download($file_path);

        }
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
        $items = array_map(function ($item) {
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
                'status' => JobInfo::WAITING,
                'name' => $request->input('name')
            ]);
            dispatch((new OkParserApi('getPostUserActivity', ['urls' => $items], $jobInfo, null)));

            $cronInfo->save();
        }
        return redirect()->back();
    }
}
