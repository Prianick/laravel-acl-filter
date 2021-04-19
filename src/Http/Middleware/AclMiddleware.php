<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Alc\AclService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AclMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        AclService::getInstance()
            ->switchStatus(true)
            ->addRestrictionsToRequest($request);

        return $next($request);
    }
}
