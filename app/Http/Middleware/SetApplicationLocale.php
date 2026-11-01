<?php

namespace App\Http\Middleware;

use App\Support\LocaleManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = LocaleManager::forRequest($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
