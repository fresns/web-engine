<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Fresns\WebEngine\Providers;

use App\Helpers\CacheHelper;
use App\Helpers\ConfigHelper;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Fresns\WebEngine\Auth\AccountGuard;
use Fresns\WebEngine\Auth\UserGuard;

class WebEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerAuthenticator();
        $this->registerTranslations();
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        if (fs_db_config('engine_status', false)) {
            $this->app->register(RouteServiceProvider::class);
        }

        config()->set('laravellocalization.useAcceptLanguageHeader', false);

        config()->set('laravellocalization.hideDefaultLocaleInURL', true);

        // Keep the default configuration if you can't query data from the database
        try {
            $defaultLangTag = ConfigHelper::fresnsConfigDefaultLangTag();

            $cacheKey = 'fresns_web_languages';
            $cacheTags = ['fresnsWeb', 'fresnsWebConfigs'];

            $supportedLocales = CacheHelper::get($cacheKey, $cacheTags);

            if (empty($supportedLocales)) {
                $langMenus = ConfigHelper::fresnsConfigByItemKey('language_menus') ?? [
                    [
                        'isEnabled' => true,
                        'langTag' => $defaultLangTag,
                        'langName' => $defaultLangTag,
                    ],
                ];

                $localeMenus = [];
                foreach ($langMenus as $menu) {
                    if (! $menu['isEnabled']) {
                        continue;
                    }

                    $localeMenus[$menu['langTag']] = ['name' => $menu['langName']];
                }

                $supportedLocales = $localeMenus;
            }
        } catch (\Throwable $e) {
            $cookiePrefix = ConfigHelper::fresnsConfigByItemKey('engine_cookie_prefix') ?? 'fresns_';
            $langCookie = "{$cookiePrefix}lang_tag";

            $defaultLangTag = \request()->header('X-Fresns-Client-Lang-Tag') ?? \request()->cookie($langCookie) ?? ConfigHelper::fresnsConfigDefaultLangTag();

            $supportedLocales = [
                $defaultLangTag => ['name' => $defaultLangTag],
            ];
        }

        config()->set('laravellocalization.supportedLocales', $supportedLocales);

        config()->set('app.locale', $defaultLangTag);
    }

    protected function registerAuthenticator(): void
    {
        app()->singleton('fresns.account', function ($app) {
            return new AccountGuard($app);
        });

        app()->singleton('fresns.user', function ($app) {
            return new UserGuard($app);
        });
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__, 2).'/lang', 'FsWeb');
    }
}
