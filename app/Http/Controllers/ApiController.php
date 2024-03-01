<?php

/*
 * Fresns (https://fresns.org)
 * Copyright (C) 2021-Present Jevan Tang
 * Released under the Apache-2.0 License.
 */

namespace Fresns\WebEngine\Http\Controllers;

use App\Utilities\ConfigUtility;
use Fresns\WebEngine\Helpers\ApiHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    // make access token
    public function makeAccessToken(): JsonResponse
    {
        $headers = Arr::except(ApiHelper::getHeaders(), ['Accept']);

        $accessToken = urlencode(base64_encode(json_encode($headers)));

        return Response::json([
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'accessToken' => $accessToken,
            ],
        ]);
    }

    // api get
    public function apiGet(Request $request, string $path): JsonResponse
    {
        $endpointPath = Str::of($path)->start('/')->toString();

        $inArray = in_array($endpointPath, [
            '/status.json',
            '/api/fresns/v1/global/status',
            '/api/fresns/v1/common/ip-info',
        ]);

        $startsWith = Str::startsWith($endpointPath, [
            '/api/fresns/v1/user/',
            '/api/fresns/v1/hashtag/',
            '/api/fresns/v1/geotag/',
            '/api/fresns/v1/post/',
            '/api/fresns/v1/comment/',
            '/api/fresns/v1/search/',
        ]);

        $pattern = '/^\/api\/fresns\/v1\/group\/\d+\/interaction\/.*$/';

        if ($inArray || $startsWith || preg_match($pattern, $endpointPath)) {
            $langTag = fs_theme('lang');

            return Response::json([
                'code' => 33100,
                'message' => ConfigUtility::getCodeMessage(33100, 'Fresns', $langTag),
                'data' => [],
            ]);
        }

        $result = ApiHelper::make()->get($endpointPath, [
            'query' => $request->all(),
        ]);

        return Response::json($result);
    }

    // api post
    public function apiPost(Request $request, string $path): JsonResponse
    {
        $endpointPath = Str::of($path)->start('/')->toString();

        switch ($endpointPath) {
            case '/api/fresns/v1/common/file/uploads':
                $result = ApiHelper::make()->post($endpointPath, [
                    'multipart' => $request->all(),
                ]);
                break;

            case '/api/fresns/v1/editor/post/publish':
                $result = ApiHelper::make()->post($endpointPath, [
                    'multipart' => $request->all(),
                ]);
                break;

            case '/api/fresns/v1/editor/comment/publish':
                $result = ApiHelper::make()->post($endpointPath, [
                    'multipart' => $request->all(),
                ]);
                break;

            default:
                $result = ApiHelper::make()->post($endpointPath, [
                    'json' => $request->all(),
                ]);
        }

        return Response::json($result);
    }

    // api put
    public function apiPut(Request $request, string $path): JsonResponse
    {
        $endpointPath = Str::of($path)->start('/')->toString();

        $result = ApiHelper::make()->put($endpointPath, [
            'json' => $request->all(),
        ]);

        return Response::json($result);
    }

    // api patch
    public function apiPatch(Request $request, string $path): JsonResponse
    {
        $endpointPath = Str::of($path)->start('/')->toString();

        $result = ApiHelper::make()->get($endpointPath, [
            'json' => $request->all(),
        ]);

        return Response::json($result);
    }

    // api delete
    public function apiDelete(Request $request, string $path): JsonResponse
    {
        $endpointPath = Str::of($path)->start('/')->toString();

        $result = ApiHelper::make()->delete($endpointPath, [
            'json' => $request->all(),
        ]);

        return Response::json($result);
    }
}
