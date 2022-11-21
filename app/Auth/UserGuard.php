<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jarvis Tang
 * Released under the Apache-2.0 License.
 */

namespace Plugins\FresnsEngine\Auth;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Plugins\FresnsEngine\Helpers\ApiHelper;

class UserGuard implements Guard
{
    /**
     * @var array
     */
    protected $user;

    /**
     * Indicates if the logout method has been called.
     *
     * @var bool
     */
    protected $loggedOut = false;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Determine if the current user is authenticated. If not, throw an exception.
     *
     * @return array
     *
     * @throws AuthenticationException|GuzzleException
     */
    public function authenticate(): array
    {
        if (! is_null($user = $this->get())) {
            return $user;
        }

        throw new AuthenticationException;
    }

    /**
     * Determine if the guard has user instance.
     *
     * @return bool
     */
    public function has(): bool
    {
        return ! is_null($this->user);
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     *
     * @throws GuzzleException
     */
    public function check(): bool
    {
        try {
            return ! is_null($this->get());
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest(): bool
    {
        return ! $this->check();
    }

    /**
     * @param  array  $user
     * @return $this
     */
    public function set(array $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param  string|null  $key
     *
     * @throws GuzzleException
     */
    public function get(?string $key = null)
    {
        if ($this->loggedOut) {
            return null;
        }

        if (! is_null($this->user)) {
            return $key ? Arr::get($this->user, $key) : $this->user;
        }

        $uid = Cookie::get('fs_uid');
        $token = Cookie::get('fs_uid_token');

        if ($uid && $token) {
            try {
                $result = ApiHelper::make()->get("/api/v2/user/{$uid}/detail");

                $this->user = data_get($result, 'data');
            } catch (\Throwable $e) {
                $this->logout();
                throw $e;
            }
        }

        if ($key) {
            return data_get($this->user, $key);
        }

        return $this->user;
    }

    public function logout(): void
    {
        Cookie::queue(Cookie::forget('fs_uid'));
        Cookie::queue(Cookie::forget('fs_uid_token'));
        Cookie::queue(Cookie::forget('timezone'));

        $this->user = null;
        $this->loggedOut = true;
    }
}
