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
use Plugins\FresnsEngine\Sdk\Factory;

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
     * Get the ID for the currently authenticated user.
     *
     * @return mixed|null
     *
     * @throws GuzzleException
     */
    public function aid()
    {
        if ($this->get()) {
            return $this->get()['aid'];
        }

        return null;
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
     * @return array|\ArrayAccess|mixed|null
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

        $uid = Cookie::get('uid');

        if ($uid) {
            $result = Factory::user()->auth->detail($uid);

            if (Arr::get($result, 'code') !== 0) {
                $this->logout();
                throw new \Exception($result['message']);
            }

            $this->user = Arr::get($result, 'data.detail');
        }

        return $key ? Arr::get($this->user, $key) : $this->user;
    }

    public function logout(): void
    {
        Cookie::queue(Cookie::forget('aid'));
        Cookie::queue(Cookie::forget('uid'));
        Cookie::queue(Cookie::forget('token'));
        Cookie::queue(Cookie::forget('timezone'));

        $this->user = null;
        $this->loggedOut = true;
    }
}
