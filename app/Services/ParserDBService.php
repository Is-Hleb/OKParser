<?php

namespace App\Services;

use App\Models\JobInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ZipArchive;

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

    public function getTables() : array{
        $tables = DB::connection('parser')->select('SHOW TABLES');
        return array_map(function ($table) {
            return $table->Tables_in_parser;
        }, $tables);
    }

    public function getAllRowsCount(string $table, bool $fromCache = false) : int
    {
        if($fromCache) {
            $count = Cache::get("$table=count", false);
            if($count) {
                return $count;
            }
        }
        try {
            $info = DB::connection('parser')->select("SELECT COUNT(*) FROM `$table`");
            $count = array_values(get_object_vars($info[0]))[0];
            Cache::set("$table=count", $count);
            return $count;
        } catch (\Exception $exception) {
            return 0;
        }
    }

    public function getRowsCount(string $table, string $row, mixed $equal, bool $fromCache = false) : int
    {
        if($fromCache) {
            $count = Cache::get("$table=$row=count", false);
            if($count) {
                return $count;
            }
        }
        $count = DB::connection('parser')->table($table)->where($row, $equal)->count();
        Cache::put("$table=$row=count", $count);
        return $count;
    }

    public function export(string $table_name, array $keys, string $export_name)
    {
        $table = DB::connection('parser')->table($table_name)->cursor();

        $table_name = str_replace(' ', '_', $export_name);
        $csv_file_path = storage_path("$table_name.csv");

        $file = fopen($csv_file_path, 'a+');
        foreach ($table as $row) {
            $info = [];
            foreach ($keys as $key) {
                $info[$key] = $row->$key ?? "";
            }
            fwrite($file, implode(",", $info) . "\n");
        }
        fclose($file);

        $zip = new ZipArchive;
        $zip_path = storage_path("$table_name.csv.zip");

        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFile($csv_file_path, "$table_name.csv");
            $zip->close();
            unlink($csv_file_path);
        }
        return $zip_path;
    }
}
