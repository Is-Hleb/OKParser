<?php

namespace App\Services;

use App\Models\JobInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ParserDBService
{

    const TABLES = [
        3 => "create table table_name
            (
                id                  int auto_increment
                    primary key,
                social_id           bigint       null,
                avatar              varchar(255) null,
                friends             tinyint      null,
                subscribers_checked tinyint      null,
                constraint social_id
                    unique (social_id)
            )
            collate = utf8mb4_general_ci;",
        1 => "create table table_name
            (
                id                  int auto_increment
                    primary key,
                social_id           bigint       null,
                friends             tinyint      null,
                avatar              varchar(255) null,
                subscribers_checked tinyint      null,
                constraint social_id
                    unique (social_id)
            )
            collate = utf8mb4_general_ci;",
    ];

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

    public function update($table, $column, $value) {
        DB::connection('parser')->table($table)->update([$column => $value]);
    }

    public function getConnection() {
        return DB::connection('parser');
    }

    public function getTables(): array
    {
        $tables = DB::connection('parser')->select('SHOW TABLES');
        return array_map(function ($table) {
            return $table->Tables_in_parser;
        }, $tables);
    }

    public function getAllRowsCount(string $table, bool $fromCache = false): int
    {
        if ($fromCache) {
            $count = Cache::get("$table=count", false);
            if ($count) {
                return $count;
            }
        }
        try {
            $info = DB::connection('parser')->select("SELECT COUNT(*) FROM `$table`");
            $count = array_values(get_object_vars($info[0]))[0];
            Cache::put("$table=count", $count);
            return $count;
        } catch (\Exception $exception) {
            return 0;
        }
    }

    public function getRowsCount(string $table, string $row, mixed $equal, bool $fromCache = false): int
    {
        if ($fromCache) {
            $count = Cache::get("$table=$row=count", false);
            if ($count) {
                return $count;
            }
        }
        $count = DB::connection('parser')->table($table)->where($row, $equal)->count();
        Cache::put("$table=$row=count", $count);
        return $count;
    }

    // Can save progress
    public function export(string $table_name, array $keys, string $export_name, bool|string $cacheId = false, $allCount = 0)
    {
        $count = 0;
        $csv_file_path = storage_path("$table_name.csv");

        $file = fopen($csv_file_path, 'a+');
        foreach (DB::connection('parser')->table($table_name)->cursor() as $row) {
            $info = [];
            foreach ($keys as $key) {
                $info[$key] = rtrim(ltrim(str_replace("\n", '', $row->$key ?? ""))) ?? "";
            }

            if(implode('', $info)) {
                fputcsv($file, $info);
                // fwrite($file, implode(",", $info) . "\n");
                if ($cacheId) {
                    dump($count);
                    Cache::put($cacheId, round(($count++ / $allCount) * 100));
                }
            }

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

    public static function cursorExport(string $table_name, array $columns)
    {
        return DB::connection('parser')->table($table_name)->select($columns)->cursor();
    }

    public static function createTableToASUPType(int $typeIndex, string $table_name)
    {
        echo $typeIndex;
        $sqlString = self::TABLES[$typeIndex];
        $sqlString = str_replace("table_name", $table_name, $sqlString);
        try {
            DB::connection('parser')->statement($sqlString);
        } catch (\Exception $exception) {

        } finally {
            return $table_name;
        }
    }

    public static function insertIntoTable($table_name, $data): void
    {
        echo json_encode($data);
        DB::connection('parser')->table($table_name)->insert($data);
    }
}
