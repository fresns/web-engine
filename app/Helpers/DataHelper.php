<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jarvis Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\FresnsEngine\Helpers;

use App\Helpers\CacheHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\LanguageHelper;
use App\Helpers\PluginHelper;
use App\Helpers\StrHelper;
use App\Models\Config;
use App\Models\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;

class DataHelper
{
    // get upload info
    public static function getConfigByItemKey(string $itemKey)
    {
        $langTag = current_lang_tag();

        $cacheKey = "fresns_web_db_config_{$itemKey}_{$langTag}";
        $nullCacheKey = CacheHelper::getNullCacheKey($cacheKey);

        // null cache count
        if (Cache::get($nullCacheKey) > CacheHelper::NULL_CACHE_COUNT) {
            return null;
        }

        $cacheTime = CacheHelper::fresnsCacheTimeByFileType(File::TYPE_ALL);

        $dbConfig = Cache::remember($cacheKey, $cacheTime, function () use ($itemKey, $langTag) {
            $config = Config::where('item_key', $itemKey)->first();

            if (! $config) {
                return null;
            }

            $itemValue = $config->item_value;

            if ($config->is_multilingual == 1) {
                $itemValue = LanguageHelper::fresnsLanguageByTableKey($config->item_key, $config->item_type, $langTag);
            } elseif ($config->item_type == 'file' && StrHelper::isPureInt($config->item_value)) {
                $itemValue = ConfigHelper::fresnsConfigFileUrlByItemKey($config->item_value);
            } elseif ($config->item_type == 'plugin') {
                $itemValue = PluginHelper::fresnsPluginUrlByUnikey($config->item_value);
            } elseif ($config->item_type == 'plugins') {
                if ($config->item_value) {
                    foreach ($config->item_value as $plugin) {
                        $pluginItem['code'] = $plugin['code'];
                        $pluginItem['url'] = PluginHelper::fresnsPluginUrlByUnikey($plugin['unikey']);
                        $itemArr[] = $pluginItem;
                    }
                    $itemValue = $itemArr;
                }
            }

            return $itemValue;
        });

        // null cache count
        if (empty($dbConfig)) {
            CacheHelper::nullCacheCount($cacheKey, $nullCacheKey);
        }

        return $dbConfig;
    }

    // get upload info
    public static function getUploadInfo(?int $usageType = null, ?string $tableName = null, ?string $tableColumn = null, ?int $tableId = null, ?string $tableKey = null)
    {
        $uploadInfo = [
            'image' => [
                'usageType' => $usageType,
                'tableName' => $tableName,
                'tableColumn' => $tableColumn,
                'tableId' => $tableId,
                'tableKey' => $tableKey,
                'type' => 'image',
            ],
            'video' => [
                'usageType' => $usageType,
                'tableName' => $tableName,
                'tableColumn' => $tableColumn,
                'tableId' => $tableId,
                'tableKey' => $tableKey,
                'type' => 'video',
            ],
            'audio' => [
                'usageType' => $usageType,
                'tableName' => $tableName,
                'tableColumn' => $tableColumn,
                'tableId' => $tableId,
                'tableKey' => $tableKey,
                'type' => 'audio',
            ],
            'document' => [
                'usageType' => $usageType,
                'tableName' => $tableName,
                'tableColumn' => $tableColumn,
                'tableId' => $tableId,
                'tableKey' => $tableKey,
                'type' => 'document',
            ],
        ];

        return $uploadInfo;
    }

    // get fresns user panel
    public static function getFresnsUserPanel(?string $key = null)
    {
        if (fs_user()->guest()) {
            return null;
        }

        $langTag = current_lang_tag();
        $uid = fs_user('detail.uid');

        $cacheKey = "fresns_web_user_panel_{$uid}_{$langTag}";

        $userPanel = Cache::remember($cacheKey, now()->addMinutes(), function () {
            $result = ApiHelper::make()->get('/api/v2/user/panel');

            return data_get($result, 'data', null);
        });

        return data_get($userPanel, $key, null);
    }

    // get fresns groups
    public static function getFresnsGroups(?string $listKey = null): array
    {
        $langTag = current_lang_tag();

        if (fs_user()->check()) {
            $uid = fs_user('detail.uid');
            $cacheKey = "fresns_web_{$uid}_groups_{$langTag}";
        } else {
            $cacheKey = "fresns_web_guest_groups_{$langTag}";
        }

        $cacheTime = CacheHelper::fresnsCacheTimeByFileType(File::TYPE_ALL, 120);

        $data = Cache::remember($cacheKey, $cacheTime, function () {
            $client = ApiHelper::make();

            $results = $client->unwrapRequests([
                'categories' => $client->getAsync('/api/v2/group/categories', [
                    'query' => [
                        'pageSize' => 100,
                        'page' => 1,
                    ],
                ]),
                'tree' => $client->getAsync('/api/v2/group/tree'),
            ]);

            $data['categories'] = data_get($results, 'categories.data.list', []);
            $data['tree'] = data_get($results, 'tree.data', []);

            return $data;
        });

        $listArr = match ($listKey) {
            'categories' => $data['categories'],
            'tree' => $data['tree'],
            default => $data,
        };

        return $listArr;
    }

    // get fresns index list
    public static function getFresnsIndexList(?string $listKey = null): array
    {
        $langTag = current_lang_tag();

        if (fs_user()->check()) {
            $uid = fs_user('detail.uid');
            $cacheKey = "fresns_web_{$uid}_index_list_{$langTag}";
        } else {
            $cacheKey = "fresns_web_guest_index_list_{$langTag}";
        }

        $cacheTime = CacheHelper::fresnsCacheTimeByFileType(File::TYPE_ALL, 120);

        $data = Cache::remember($cacheKey, $cacheTime, function () {
            $userQuery = QueryHelper::configToQuery(QueryHelper::TYPE_USER);
            $groupQuery = QueryHelper::configToQuery(QueryHelper::TYPE_GROUP);
            $hashtagQuery = QueryHelper::configToQuery(QueryHelper::TYPE_HASHTAG);
            $postQuery = QueryHelper::configToQuery(QueryHelper::TYPE_POST);
            $commentQuery = QueryHelper::configToQuery(QueryHelper::TYPE_COMMENT);

            $client = ApiHelper::make();

            $results = $client->unwrapRequests([
                'users' => $client->getAsync('/api/v2/user/list', [
                    'query' => $userQuery,
                ]),
                'groups' => $client->getAsync('/api/v2/group/list', [
                    'query' => $groupQuery,
                ]),
                'hashtags' => $client->getAsync('/api/v2/hashtag/list', [
                    'query' => $hashtagQuery,
                ]),
                'posts' => $client->getAsync('/api/v2/post/list', [
                    'query' => $postQuery,
                ]),
                'comments' => $client->getAsync('/api/v2/comment/list', [
                    'query' => $commentQuery,
                ]),
            ]);

            $data['users'] = data_get($results, 'users.data.list', []);
            $data['groups'] = data_get($results, 'groups.data.list', []);
            $data['hashtags'] = data_get($results, 'hashtags.data.list', []);
            $data['posts'] = data_get($results, 'posts.data.list', []);
            $data['comments'] = data_get($results, 'comments.data.list', []);

            return $data;
        });

        $listArr = match ($listKey) {
            'users' => $data['users'],
            'groups' => $data['groups'],
            'hashtags' => $data['hashtags'],
            'posts' => $data['posts'],
            'comments' => $data['comments'],
            default => $data,
        };

        return $listArr;
    }

    // get fresns list
    public static function getFresnsList(?string $listKey = null): array
    {
        $langTag = current_lang_tag();

        if (fs_user()->check()) {
            $uid = fs_user('detail.uid');
            $cacheKey = "fresns_web_{$uid}_list_{$langTag}";
        } else {
            $cacheKey = "fresns_web_guest_list_{$langTag}";
        }

        $cacheTime = CacheHelper::fresnsCacheTimeByFileType(File::TYPE_ALL, 120);

        $data = Cache::remember($cacheKey, $cacheTime, function () {
            $userQuery = QueryHelper::configToQuery(QueryHelper::TYPE_USER_LIST);
            $groupQuery = QueryHelper::configToQuery(QueryHelper::TYPE_GROUP_LIST);
            $hashtagQuery = QueryHelper::configToQuery(QueryHelper::TYPE_HASHTAG_LIST);
            $postQuery = QueryHelper::configToQuery(QueryHelper::TYPE_POST_LIST);
            $commentQuery = QueryHelper::configToQuery(QueryHelper::TYPE_COMMENT_LIST);

            $client = ApiHelper::make();

            $results = $client->unwrapRequests([
                'users' => $client->getAsync('/api/v2/user/list', [
                    'query' => $userQuery,
                ]),
                'groups' => $client->getAsync('/api/v2/group/list', [
                    'query' => $groupQuery,
                ]),
                'hashtags' => $client->getAsync('/api/v2/hashtag/list', [
                    'query' => $hashtagQuery,
                ]),
                'posts' => $client->getAsync('/api/v2/post/list', [
                    'query' => $postQuery,
                ]),
                'comments' => $client->getAsync('/api/v2/comment/list', [
                    'query' => $commentQuery,
                ]),
            ]);

            $data['users'] = data_get($results, 'users.data.list', []);
            $data['groups'] = data_get($results, 'groups.data.list', []);
            $data['hashtags'] = data_get($results, 'hashtags.data.list', []);
            $data['posts'] = data_get($results, 'posts.data.list', []);
            $data['comments'] = data_get($results, 'comments.data.list', []);

            return $data;
        });

        $listArr = match ($listKey) {
            'users' => $data['users'],
            'groups' => $data['groups'],
            'hashtags' => $data['hashtags'],
            'posts' => $data['posts'],
            'comments' => $data['comments'],
            default => $data,
        };

        return $listArr;
    }

    // get fresns sticky posts
    public static function getFresnsStickyPosts(?string $gid = null): array
    {
        $langTag = current_lang_tag();

        if (empty($gid)) {
            $cacheKey = "fresns_web_sticky_posts_{$langTag}";
            $query = [
                'stickyState' => 3,
            ];
        } else {
            $cacheKey = "fresns_web_{$gid}_sticky_posts_{$langTag}";
            $query = [
                'gid' => $gid,
                'stickyState' => 2,
            ];
        }
        $nullCacheKey = CacheHelper::getNullCacheKey($cacheKey);

        if (Cache::get($nullCacheKey) > CacheHelper::NULL_CACHE_COUNT) {
            return [];
        }

        $cacheTime = CacheHelper::fresnsCacheTimeByFileType(File::TYPE_ALL, 360);

        $list = Cache::remember($cacheKey, $cacheTime, function () use ($query) {
            $result = ApiHelper::make()->get('/api/v2/post/list', [
                'query' => $query,
            ]);

            return data_get($result, 'data.list', []);
        });

        // null cache count
        if (empty($list)) {
            CacheHelper::nullCacheCount($cacheKey, $nullCacheKey);
        }

        return $list;
    }

    // get fresns sticky comments
    public static function getFresnsStickyComments(string $pid): array
    {
        $langTag = current_lang_tag();

        $cacheKey = "fresns_web_{$pid}_sticky_comments_{$langTag}";
        $nullCacheKey = CacheHelper::getNullCacheKey($cacheKey);

        if (Cache::get($nullCacheKey) > CacheHelper::NULL_CACHE_COUNT) {
            return [];
        }

        $list = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($pid) {
            $result = ApiHelper::make()->get('/api/v2/comment/list', [
                'query' => [
                    'pid' => $pid,
                    'sticky' => true,
                ],
            ]);

            return data_get($result, 'data.list', []);
        });

        // null cache count
        if (empty($list)) {
            CacheHelper::nullCacheCount($cacheKey, $nullCacheKey, 10);
        }

        return $list;
    }

    // get fresns content types
    public static function getFresnsContentTypes(string $type): array
    {
        $langTag = current_lang_tag();

        $cacheKey = "fresns_web_{$type}_content_types_{$langTag}";
        $nullCacheKey = CacheHelper::getNullCacheKey($cacheKey);

        if (Cache::get($nullCacheKey) > CacheHelper::NULL_CACHE_COUNT) {
            return [];
        }

        $list = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($type) {
            $result = ApiHelper::make()->get("/api/v2/global/{$type}/content-types");

            return data_get($result, 'data', []);
        });

        // null cache count
        if (empty($list)) {
            CacheHelper::nullCacheCount($cacheKey, $nullCacheKey, 10);
        }

        return $list;
    }

    // cache forget account and user
    public static function cacheForgetAccountAndUser()
    {
        $cookiePrefix = fs_db_config('engine_cookie_prefix', 'fresns_');

        $aid = Cookie::get("{$cookiePrefix}aid");
        $uid = Cookie::get("{$cookiePrefix}uid");

        CacheHelper::forgetFresnsMultilingual("fresns_web_account_{$aid}");
        CacheHelper::forgetFresnsMultilingual("fresns_web_user_{$uid}");
    }
}
