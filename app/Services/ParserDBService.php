<?php

namespace App\Services;

use App\Models\JobInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ParserDBService
{
    private const MUSIC_COLLECTION_TABLE = "create table `table_name`
(
    id       bigint auto_increment,
    source   varchar(255) null,
    name     varchar(255) null,
    listeners bigint       null,
    image    varchar(255) null,
    constraint table_name_pk
        primary key (id)
);";

    private const USERS_TABLE = "create table if not exists `table_name`
(
    id                  int auto_increment
        primary key,
    name                varchar(255) null,
    city                varchar(255) null,
    region              varchar(255) null,
    age                 int          null,
    gender              varchar(255) null,
    social_id           bigint       not null,
    avatar              varchar(255) null,
    country             varchar(255) null,
    age_range           varchar(255) null,
    edu                 varchar(255) null,
    work                varchar(255) null,
    friends             tinyint      null,
    edu_checked         tinyint      null,
    subscribers_checked tinyint      null,
    posts_checked       tinyint      null,
    groups_checked      tinyint      null,
    constraint social_id
        unique (social_id)
)
    collate = utf8mb4_general_ci;

";

    private const POST_DETAIL_TABLE = "create table `table_nae`
(
    id          bigint auto_increment,
    source      varchar(255) null,
    text        longtext     null,
    images      json         null,
    likes       int          null,
    comments    int          null,
    reposts     int          null,
    source_link varchar(255) null,
    owner_link  varchar(255) null,
    constraint table_name_pk
        primary key (id)
);";

    const TABLES = [
        3 => self::USERS_TABLE,
        1 => self::USERS_TABLE,
        16 => self::USERS_TABLE,
        8 => self::USERS_TABLE,
        4 => self::USERS_TABLE,
        2 => self::USERS_TABLE,
        17 => self::MUSIC_COLLECTION_TABLE,
        19 => self::POST_DETAIL_TABLE
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
            Cache::set("$table=count", $count);
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
