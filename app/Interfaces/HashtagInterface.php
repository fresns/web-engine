<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\FresnsEngine\Interfaces;

use App\Fresns\Api\Http\Controllers\CommentController;
use App\Fresns\Api\Http\Controllers\PostController;
use App\Fresns\Api\Http\Controllers\HashtagController;
use Illuminate\Http\Request;
use Plugins\FresnsEngine\Exceptions\ErrorException;
use Plugins\FresnsEngine\Helpers\ApiHelper;
use Plugins\FresnsEngine\Helpers\DataHelper;

class HashtagInterface
{
    public static function list(?array $query = []): array
    {
        if (is_remote_api()) {
            return ApiHelper::make()->get('/api/v2/hashtag/list', [
                'query' => $query,
            ]);
        }

        if (fs_api_config('site_mode') == 'private' && fs_api_config('site_private_end_after') == 1 && fs_user('detail.expired')) {
            return DataHelper::getApiDataTemplate();
        }

        try {
            $request = Request::create('/api/v2/hashtag/list', 'GET', $query);

            $apiController = new HashtagController();
            $response = $apiController->list($request);

            $resultContent = $response->getContent();
            $result = json_decode($resultContent, true);
        } catch (\Exception $e) {
            throw new ErrorException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    public static function detail(string $hid, ?string $type = null, ?array $query = []): array
    {
        $type = match ($type) {
            'posts' => 'posts',
            'comments' => 'comments',
            default => 'posts',
        };

        if (fs_api_config('site_mode') == 'private' && fs_api_config('site_private_end_after') == 1 && fs_user('detail.expired')) {
            $results = [
                'hashtag' => DataHelper::getApiDataTemplate('detail'),
                'posts' => CommentInterface::list($query),
                'comments' => CommentInterface::list($query),
            ];

            return $results;
        }

        if (is_remote_api()) {
            $client = ApiHelper::make();

            switch ($type) {
                case 'posts':
                    $results = $client->unwrapRequests([
                        'hashtag' => $client->getAsync("/api/v2/hashtag/{$hid}/detail"),
                        'posts' => $client->getAsync('/api/v2/post/list', [
                            'query' => $query,
                        ]),
                    ]);
                    break;

                case 'comments':
                    $results = $client->unwrapRequests([
                        'hashtag' => $client->getAsync("/api/v2/hashtag/{$hid}/detail"),
                        'comments' => $client->getAsync('/api/v2/comment/list', [
                            'query' => $query,
                        ]),
                    ]);
                    break;
            }

            return $results;
        }

        try {
            $apiController = new HashtagController();
            $response = $apiController->detail($hid);

            $resultContent = $response->getContent();
            $result = json_decode($resultContent, true);

            switch ($type) {
                case 'posts':
                    $request = Request::create('/api/v2/post/list', 'GET', $query);
                    $apiPostController = new PostController();

                    $response = $apiPostController->list($request);

                    $resultContent = $response->getContent();

                    $results = [
                        'hashtag' => $result,
                        'posts' => json_decode($resultContent, true),
                    ];
                    break;

                case 'comments':
                    $request = Request::create('/api/v2/comment/list', 'GET', $query);
                    $apiCommentController = new CommentController();

                    $response = $apiCommentController->list($request);

                    $resultContent = $response->getContent();

                    $results = [
                        'hashtag' => $result,
                        'comments' => json_decode($resultContent, true),
                    ];
                    break;
            }
        } catch (\Exception $e) {
            throw new ErrorException($e->getMessage(), $e->getCode());
        }

        return $results;
    }
}