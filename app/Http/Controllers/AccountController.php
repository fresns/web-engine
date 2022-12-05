<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jarvis Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\FresnsEngine\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Plugins\FresnsEngine\Exceptions\ErrorException;
use Plugins\FresnsEngine\Helpers\ApiHelper;
use Plugins\FresnsEngine\Helpers\QueryHelper;

class AccountController extends Controller
{
    // register
    public function register(Request $request)
    {
        if (fs_account()->check() || fs_user()->check()) {
            return redirect()->intended(fs_route(route('fresns.home')));
        }

        return view('account.register');
    }

    // login
    public function login(Request $request)
    {
        if (fs_account()->check() && fs_user()->check()) {
            return redirect()->intended(fs_route(route('fresns.home')));
        }

        return view('account.login');
    }

    // logout
    public function logout()
    {
        fs_account()->logout();

        ApiHelper::make()->delete('/api/v2/account/logout');

        return redirect()->intended(fs_route(route('fresns.home')));
    }

    // reset password
    public function resetPassword(Request $request)
    {
        if (fs_account()->check() || fs_user()->check()) {
            return redirect()->intended(fs_route(route('fresns.account.index')));
        }

        return view('account.reset-password');
    }

    // index
    public function index()
    {
        return view('account.index');
    }

    // wallet
    public function wallet(Request $request)
    {
        $result = ApiHelper::make()->get('/api/v2/account/wallet-logs', [
            'query' => $request->all(),
        ]);

        if (data_get($result, 'code') !== 0) {
            throw new ErrorException($result['message'], $result['code']);
        }

        $logs = QueryHelper::convertApiDataToPaginate(
            items: $result['data']['list'],
            paginate: $result['data']['paginate'],
        );

        return view('account.wallet', compact('logs'));
    }

    // users
    public function users()
    {
        $multiUserStatus = false;

        if (fs_api_config('multi_user_status')) {
            $roleIds = Arr::pluck(fs_user()->get('detail.users.roles'), 'rid');

            $multiUserStatus = in_array($roleIds, fs_api_config('multi_user_roles'));
        }

        return view('account.users', compact('multiUserStatus'));
    }

    // settings
    public function settings()
    {
        return view('account.settings');
    }
}
