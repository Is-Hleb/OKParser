<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\OkParserApi;
use App\Models\CronTaskinfo;
use App\Models\JobInfo;
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

    public function postOutput(string $mode, int $id)
    {
        $cronTab = CronTaskinfo::find($id);
        switch ($mode) {
            case "tab":
                $result = $cronTab->output;
                if (!$result) {
                    abort(302, "Результатов по этой задаче нет");
                }
                $dirPath = storage_path("cron_{$cronTab->id}");
                if (!is_dir($dirPath)) {
                    mkdir($dirPath);
                }
                foreach ($result as $date => $output) {
                    $file_path = $dirPath . "/cron_{$cronTab->id}_result_$date.csv";
                    $file = fopen($file_path, 'w');
                    foreach ($output as $data) {
                        fputcsv($file, $data);
                    }
                    fclose($file);
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
                $tabs = CronTaskinfo::where('name', $cronTab->name)->get();
                foreach ($tabs as $cronTab) {
                    $result = $cronTab->output;
                    if (!$result) {
                        abort(302, "Результатов по этой задаче нет");
                    }
                    $dirPath = storage_path("cron_{$cronTab->id}");
                    if (!is_dir($dirPath)) {
                        mkdir($dirPath);
                    }
                    foreach ($result as $date => $output) {
                        $file_path = $dirPath . "/cron_{$cronTab->id}_result_$date.csv";
                        $file = fopen($file_path, 'w');
                        foreach ($output as $data) {
                            fputcsv($file, $data);
                        }
                        fclose($file);
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
                        response()->download(storage_path($zip_path));
                    }
                }
                break;
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
