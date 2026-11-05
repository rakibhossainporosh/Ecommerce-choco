<?php

namespace App\Http\Middleware;

use App\Support\StorefrontSeo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareStorefrontSeo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*', 'livewire/*') || $request->routeIs('seo.robots', 'seo.sitemap')) {
            return $next($request);
        }

        $seo = StorefrontSeo::for($request);
        $request->attributes->set('storefront_seo', $seo);
        View::share('storefrontSeo', $seo);

        $response = $next($request);

        if (str_starts_with($seo['robots'], 'noindex')) {
            $response->headers->set('X-Robots-Tag', $seo['robots']);
        }

        return $response;
    }
}
