<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Fresns\WebEngine\Http\Controllers;

use App\Helpers\ConfigHelper;
use Browser;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class PortalController extends Controller
{
    public function index()
    {
        $portalContent = Browser::isMobile() ? ConfigHelper::fresnsConfigByItemKey('portal_3') : ConfigHelper::fresnsConfigByItemKey('portal_2');

        $content = ConfigHelper::fresnsConfigByItemKey('portal_4') ?? $portalContent;

        return view('portal.index', compact('content'));
    }

    public function customPage(string $name)
    {
        if ($name == 'index') {
            return redirect(fs_route(route('fresns.portal')));
        }

        $viewName = "portal.{$name}";

        if ($name == 'private' || ! View::exists($viewName)) {
            return Response::view('error', [
                'code' => 404,
                'message' => 'Page Not Found',
            ], 404);
        }

        return view($viewName);
    }
}
