<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Fresns\WebEngine\Helpers;

use App\Helpers\CacheHelper;
use App\Helpers\ConfigHelper;
use App\Models\File;
use App\Models\Post;
use App\Utilities\ConfigUtility;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class DataHelper
{
    // get api data
    public static function getApiDataTemplate(?string $type = 'list'): array
    {
        $message = ConfigUtility::getCodeMessage(35303, 'Fresns', fs_theme('lang')) ?? 'Unknown Warning';

        $data = [
            'code' => 0,
            'message' => "[35303] {$message}",
            'data' => [
                'pagination' => [
                    'total' => 0,
                    'pageSize' => 15,
                    'currentPage' => 1,
                    'lastPage' => 1,
                ],
                'list' => [],
            ],
        ];

        if ($type == 'list') {
            return $data;
        }

        return [
            'code' => 35303,
            'message' => $message,
            'data' => [],
        ];
    }

    // get editor url
    public static function getEditorUrl(string $url, string $type, ?string $did = null, ?string $fsid = null): string
    {
        $headers = Arr::except(ApiHelper::getHeaders(), ['Accept']);

        $accessToken = urlencode(base64_encode(json_encode($headers)));

        $scene = match ($type) {
            'post' => 'postEditor',
            'comment' => 'commentEditor',
            default => 'postEditor',
        };

        $pluginUrl = Str::replace('{accessToken}', $accessToken, $url);
        $pluginUrl = Str::replace('{type}', $type, $pluginUrl);
        $pluginUrl = Str::replace('{scene}', $scene, $pluginUrl);

        if ($did) {
            $pluginUrl = Str::replace('{did}', $did, $pluginUrl);
        }

        if ($fsid) {
            $fsidName = match ($type) {
                'post' => '{pid}',
                'comment' => '{cid}',
                default => '{pid}',
            };

            $pluginUrl = Str::replace($fsidName, $fsid, $pluginUrl);
        }

        return $pluginUrl;
    }

    // get fresns group tree
    public static function getFresnsGroupTree(): ?array
    {
        $langTag = fs_theme('lang');

        if (fs_user()->check()) {
            $uid = fs_user('detail.uid');
            $cacheKey = "fresns_web_group_tree_by_{$uid}_{$langTag}";
        } else {
            $cacheKey = "fresns_web_group_tree_by_guest_{$langTag}";
        }

        // is known to be empty
        $isKnownEmpty = CacheHelper::isKnownEmpty($cacheKey);
        if ($isKnownEmpty) {
            return [];
        }

        $cacheTag = 'fresnsWeb';

        // get cache
        $groupTree = CacheHelper::get($cacheKey, $cacheTag);

        if (empty($groupTree)) {
            $result = ApiHelper::make()->get('/api/fresns/v1/group/tree');

            $groupTree = data_get($result, 'data', []);

            $cacheTime = CacheHelper::fresnsCacheTimeByFileType(File::TYPE_ALL, 60);
            CacheHelper::put($groupTree, $cacheKey, $cacheTag, null, $cacheTime);
        }

        return $groupTree ?? [];
    }

    // get fresns content list
    public static function getFresnsContentList(string $channel, string $type): ?array
    {
        $channelArr = [
            'user',
            'group',
            'hashtag',
            'geotag',
            'post',
            'comment',
        ];

        $typeArr = [
            'home',
            'list',
        ];

        if (! in_array($channel, $channelArr) || ! in_array($type, $typeArr)) {
            return [];
        }

        $langTag = fs_theme('lang');

        if (fs_user()->check()) {
            $uid = fs_user('detail.uid');
            $cacheKey = "fresns_web_content_{$channel}_{$type}_by_{$uid}_{$langTag}";
        } else {
            $cacheKey = "fresns_web_content_{$channel}_{$type}_by_guest_{$langTag}";
        }

        // is known to be empty
        $isKnownEmpty = CacheHelper::isKnownEmpty($cacheKey);
        if ($isKnownEmpty) {
            return [];
        }

        $cacheTag = 'fresnsWeb';

        // get cache
        $listArr = CacheHelper::get($cacheKey, $cacheTag);

        if (empty($listArr)) {
            $queryType = $channel;
            if ($type == 'list') {
                $queryType = $channel.'_list';
            }

            $queryConfig = QueryHelper::configToQuery($queryType);

            $result = ApiHelper::make()->get("/api/fresns/v1/{$channel}/list", [
                'query' => $queryConfig,
            ]);

            $listArr = data_get($result, 'data.list', []);

            $cacheTime = CacheHelper::fresnsCacheTimeByFileType(File::TYPE_ALL, 60);
            CacheHelper::put($listArr, $cacheKey, $cacheTag, null, $cacheTime);
        }

        return $listArr ?? [];
    }

    // get fresns sticky posts
    public static function getFresnsStickyPosts(?string $gid = null): array
    {
        $langTag = fs_theme('lang');

        if (empty($gid)) {
            $cacheKey = "fresns_web_sticky_posts_by_global_{$langTag}";
            $query = [
                'stickyState' => Post::STICKY_GLOBAL,
            ];
        } else {
            $cacheKey = "fresns_web_sticky_posts_by_group_{$gid}_{$langTag}";
            $query = [
                'gid' => $gid,
                'stickyState' => Post::STICKY_GROUP,
            ];
        }

        // is known to be empty
        $isKnownEmpty = CacheHelper::isKnownEmpty($cacheKey);
        if ($isKnownEmpty) {
            return [];
        }

        $cacheTag = 'fresnsWeb';

        // get cache
        $listArr = CacheHelper::get($cacheKey, $cacheTag);

        if (empty($listArr)) {
            $result = ApiHelper::make()->get('/api/fresns/v1/post/list', [
                'query' => $query,
            ]);

            $listArr = data_get($result, 'data.list', []);

            $cacheTime = CacheHelper::fresnsCacheTimeByFileType(File::TYPE_ALL, 360);
            CacheHelper::put($listArr, $cacheKey, $cacheTag, null, $cacheTime);
        }

        return $listArr ?? [];
    }

    // get fresns sticky comments
    public static function getFresnsStickyComments(string $pid): array
    {
        $langTag = fs_theme('lang');

        $cacheKey = "fresns_web_sticky_comments_by_{$pid}_{$langTag}";
        $cacheTag = 'fresnsWeb';

        // is known to be empty
        $isKnownEmpty = CacheHelper::isKnownEmpty($cacheKey);
        if ($isKnownEmpty) {
            return [];
        }

        // get cache
        $listArr = CacheHelper::get($cacheKey, $cacheTag);

        if (empty($listArr)) {
            $result = ApiHelper::make()->get('/api/fresns/v1/comment/list', [
                'query' => [
                    'pid' => $pid,
                    'sticky' => true,
                ],
            ]);

            $listArr = data_get($result, 'data.list', []);

            $cacheTime = CacheHelper::fresnsCacheTimeByFileType(File::TYPE_ALL, 360);
            CacheHelper::put($listArr, $cacheKey, $cacheTag, null, $cacheTime);
        }

        return $listArr ?? [];
    }

    // cache forget account and user
    public static function cacheForgetAccountAndUser()
    {
        $cookiePrefix = ConfigHelper::fresnsConfigByItemKey('website_cookie_prefix') ?? 'fresns_';

        $aid = Cookie::get("{$cookiePrefix}aid");
        $uid = Cookie::get("{$cookiePrefix}uid");

        CacheHelper::forgetFresnsMultilingual("fresns_web_account_{$aid}", 'fresnsWeb');
        CacheHelper::forgetFresnsMultilingual("fresns_web_user_{$uid}", 'fresnsWeb');
    }
}
