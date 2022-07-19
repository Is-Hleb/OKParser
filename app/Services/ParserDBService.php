<?php

namespace App\Services;

use App\Models\JobInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ParserDBService
{

    public function getInfos()
    {
        $tables = DB::connection('parser')->select('SHOW TABLES');
        $tables = array_map(function ($table) {
            return $table->Tables_in_parser;
        }, $tables);

        $tables = array_filter($tables, fn($table) => str_starts_with($table, "parser_task"));
        $infos = [];
        foreach ($tables as $table) {
            $info = Cache::get($table, false);
            if (!$info) {
                $info = DB::connection('parser')->select('SELECT COUNT(*) FROM search_links WHERE `table` = "' . $table . '"');
                $job_id = explode('_', $table)[2];
                $info = [
                    'task_id' => $job_id,
                    'count' => array_values(get_object_vars($info[0]))[0],
                    'table_name' => $table,
                    'name' => JobInfo::find($job_id)->name ?? "Не задано",
                    'users_count' => get_object_vars(DB::connection('parser')->select("SELECT COUNT(*) FROM `$table`")[0])['COUNT(*)']
                ];
                Cache::put($table, $info);
            }
            $infos[] = $info;
        }

        return $infos;
    }

}
