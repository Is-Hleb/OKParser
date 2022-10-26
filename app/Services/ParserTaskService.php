<?php

namespace App\Services;

use App\Models\Parser;
use App\Models\ParserTask;
use App\Models\ParserTask as ParserTaskModel;
use App\Models\ParserType;
use Illuminate\Support\Str;

class ParserTaskService
{

    const USER_SUBSCRIBERS = 1;
    const USER_POSTS = 2;
    const USER_FRIENDS = 3;
    const USERS_DETAIL = 4;
    const GROUP_FOLLOWERS = 5;
    const GROUP_POSTS = 6;
    const USERS_BY_CITIES = 8;
    const USERS_AVATARS = "4-avatars";

    public static function dispachTask(int $type, string $id, array $logins): bool
    {
        $table_name = $id;
        $type = ParserType::where('index', $type)->get()->first();
        $inputLogins = $logins;

        if($type === ParserType::GROUP_DETAIL) {
            self::setGroupsDetail($logins);
        }

        if ($type) {
            if (
                $type->index == ParserType::FRIENDS
                || $type->index == ParserType::SUBSCRIBERS
                || $type->index == ParserType::GROUPS
                || $type->index == ParserType::DETAIL
                || $type->index == ParserType::USER_POSTS
                || $type->index == ParserType::MUSIC_ALBUMS
                || $type->index == ParserType::USER_POST_DETAIL
                || $type->index == ParserType::GROUP_POST_DETAIL
            ) {

                if (
                    $type->index == ParserType::MUSIC_ALBUMS
                    || $type->index == ParserType::USER_POST_DETAIL
                    || $type->index == ParserType::GROUP_POST_DETAIL
                ) {
                    $logins = array_map(function ($item) {
                        return ['source' => $item];
                    }, $logins);
                } else {
                    $logins = array_map(function ($item) {
                        return ['social_id' => $item];
                    }, $logins);
                }

                $users_table_name = $table_name . '_users_' . $type->index;
                if (
                    $type->index == ParserType::DETAIL
                    || $type->index == ParserType::MUSIC_ALBUMS
                    || $type->index == ParserType::USER_POST_DETAIL
                    || $type->index == ParserType::GROUP_POST_DETAIL
                ) {
                    $users_table_name = $id;
                }

                ParserDBService::createTableToASUPType(
                    $type->index,
                    $users_table_name
                );

                ParserDBService::insertIntoTable($users_table_name, $logins);

                if (
                    $type->index == ParserType::DETAIL
                    || $type->index == ParserType::MUSIC_ALBUMS
                    || $type->index == ParserType::USER_POST_DETAIL
                    || $type->index == ParserType::GROUP_POST_DETAIL
                ) {
                    ParserTaskModel::create([
                        'selected_table' => "",
                        'table_name' => $users_table_name,
                        'type' => $type->index,
                        'is_asup_task' => true,
                        'type_id' => $type->id,
                        'name' => $id,
                        'logins' => json_encode($inputLogins)
                    ]);
                } else {
                    ParserTaskModel::create([
                        'selected_table' => $users_table_name,
                        'table_name' => $table_name,
                        'type' => $type->index,
                        'is_asup_task' => true,
                        'type_id' => $type->id,
                        'name' => $id,
                        'logins' => json_encode($inputLogins)
                    ]);
                }
                return true;
            } else if ($type->index == ParserType::USERS_BY_CITIES) {
                ParserTaskModel::create([
                    'selected_table' => $table_name . '_8',
                    'table_name' => $table_name,
                    'type' => $type->index,
                    'is_asup_task' => true,
                    'type_id' => $type->id,
                    'name' => $id,
                    'logins' => json_encode($inputLogins)
                ]);
            }
        } else {
            return false;
        }
        return false;
    }

    private static function setGroupsDetail(array $logins, bool $isAsup = true)
    {
        $table_name = Str::random(5) . '_' . 'group_detail';

        ParserDBService::createTableToASUPType(ParserType::GROUP_DETAIL, $table_name);
        ParserDBService::insertIntoTable($table_name, array_map(function ($item) {
            return ['source' => $item];
        }, $logins));

        ParserTaskModel::create([
            'table_name' => $table_name,
            'type' => ParserType::GROUP_DETAIL,
            'is_asup_task' => $isAsup,
            'type_id' => ParserType::where('index', ParserType::GROUP_DETAIL)->first()->id,
            'name' => 'group_detail',
            'logins' => json_encode($logins)
        ]);
    }

    public static function usersByCities(string $country, array $cities, string|null $taskName = null, bool $isAsupTask = false): void
    {
        $table_name = Str::random(5);
        ParserTaskModel::create([
            'table_name' => $table_name,
            'type' => self::USERS_BY_CITIES,
            'is_asup_task' => $isAsupTask,
            'type_id' => ParserType::where('index', 8)->first()->id,
            'name' => $taskName,
            'logins' => json_encode([
                'country' => $country,
                'cities' => $cities
            ])
        ]);
    }

    public static function userFriends(string|null $users_table = null, string|null $taskName = null, bool $isAsupTask = false): void
    {
        $friendsTable = Str::random(5);
        ParserTaskModel::create([
            'table_name' => $friendsTable,
            'selected_table' => $users_table,
            'type' => self::USER_FRIENDS,
            'name' => $taskName,
            'is_asup_task' => $isAsupTask,
            'type_id' => ParserType::where('index', self::USER_FRIENDS)->first()->id,
        ]);
    }

    public static function userAvatars(string|null $users_table = null, string|null $taskName = null, bool $isAsupTask = false): void
    {

        $avatarsTable = Str::random(5);
        ParserTaskModel::create([
            'table_name' => $avatarsTable,
            'selected_table' => $users_table,
            'type' => self::USERS_AVATARS,
            'is_asup_task' => $isAsupTask,
            'name' => $taskName,
            'type_id' => ParserType::where('index', self::USERS_AVATARS)->first()->id,
        ]);
    }

    public static function userSubscribers(string|null $users_table = null, string|null $taskName = null, bool $isAsupTask = false): void
    {

        $subscribersTable = Str::random(5);
        ParserTaskModel::create([
            'table_name' => $subscribersTable,
            'selected_table' => $users_table,
            'type' => self::USER_SUBSCRIBERS,
            'is_asup_task' => $isAsupTask,
            'name' => $taskName,
            'type_id' => ParserType::where('index', self::USER_SUBSCRIBERS)->first()->id,
        ]);
    }
}
