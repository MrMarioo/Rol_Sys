<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    protected const MAX_PER_PAGE = 100;

    protected function perPage(Request $request, ?int $default = null): int
    {
        $perPage = $request->integer(key: 'per_page', default: $default ?? config('pagination.list'));

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }
}
