<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Fresns\WebEngine\Http\Controllers;

use Fresns\WebEngine\Exceptions\ErrorException;
use Fresns\WebEngine\Helpers\QueryHelper;
use Fresns\WebEngine\Interfaces\SearchInterface;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    // index
    public function index(Request $request)
    {
        $searchType = $request->get('searchType');
        $searchKey = $request->get('searchKey');

        if (empty($searchType)) {
            return view("{$this->viewNamespace}::search.index");
        }

        switch ($searchType) {
            // user
            case 'user':
                return redirect()->to(fs_route(route('fresns.search.users', ['searchKey' => $searchKey])));
                break;

                // group
            case 'group':
                return redirect()->to(fs_route(route('fresns.search.groups', ['searchKey' => $searchKey])));
                break;

                // hashtag
            case 'hashtag':
                return redirect()->to(fs_route(route('fresns.search.hashtags', ['searchKey' => $searchKey])));
                break;

                // post
            case 'post':
                return redirect()->to(fs_route(route('fresns.search.posts', ['searchKey' => $searchKey])));
                break;

                // comment
            case 'comment':
                return redirect()->to(fs_route(route('fresns.search.comments', ['searchKey' => $searchKey])));
                break;

                // default
            default:
                return view("{$this->viewNamespace}::search.index");
                break;
        }
    }

    // users
    public function users(Request $request)
    {
        $query = $request->all();

        $result = SearchInterface::search('users', $query);

        if (data_get($result, 'code') !== 0) {
            throw new ErrorException($result['message'], $result['code']);
        }

        $users = QueryHelper::convertApiDataToPaginate(
            items: $result['data']['list'],
            paginate: $result['data']['paginate'],
        );

        return view("{$this->viewNamespace}::search.users", compact('users'));
    }

    // groups
    public function groups(Request $request)
    {
        $query = $request->all();

        $result = SearchInterface::search('groups', $query);

        if (data_get($result, 'code') !== 0) {
            throw new ErrorException($result['message'], $result['code']);
        }

        $groups = QueryHelper::convertApiDataToPaginate(
            items: $result['data']['list'],
            paginate: $result['data']['paginate'],
        );

        return view("{$this->viewNamespace}::search.groups", compact('groups'));
    }

    // hashtags
    public function hashtags(Request $request)
    {
        $query = $request->all();

        $result = SearchInterface::search('hashtags', $query);

        if (data_get($result, 'code') !== 0) {
            throw new ErrorException($result['message'], $result['code']);
        }

        $hashtags = QueryHelper::convertApiDataToPaginate(
            items: $result['data']['list'],
            paginate: $result['data']['paginate'],
        );

        return view("{$this->viewNamespace}::search.hashtags", compact('hashtags'));
    }

    // posts
    public function posts(Request $request)
    {
        $query = $request->all();

        $result = SearchInterface::search('posts', $query);

        if (data_get($result, 'code') !== 0) {
            throw new ErrorException($result['message'], $result['code']);
        }

        $posts = QueryHelper::convertApiDataToPaginate(
            items: $result['data']['list'],
            paginate: $result['data']['paginate'],
        );

        return view("{$this->viewNamespace}::search.posts", compact('posts'));
    }

    // comments
    public function comments(Request $request)
    {
        $query = $request->all();

        $result = SearchInterface::search('comments', $query);

        if (data_get($result, 'code') !== 0) {
            throw new ErrorException($result['message'], $result['code']);
        }

        $comments = QueryHelper::convertApiDataToPaginate(
            items: $result['data']['list'],
            paginate: $result['data']['paginate'],
        );

        return view("{$this->viewNamespace}::search.comments", compact('comments'));
    }
}
