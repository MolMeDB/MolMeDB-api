<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeLivewireUploadUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('livewire.upload-file') || $request->hasValidSignature()) {
            return $next($request);
        }

        $canonicalRequest = Request::create(
            rtrim((string) config('app.url'), '/').$request->getRequestUri(),
            $request->getMethod(),
        );

        if (! $canonicalRequest->hasValidSignature()) {
            return $next($request);
        }

        $this->useCanonicalAuthority($request);

        return $next($request);
    }

    private function useCanonicalAuthority(Request $request): void
    {
        $appUrl = parse_url((string) config('app.url'));
        $scheme = $appUrl['scheme'] ?? 'https';
        $host = $appUrl['host'] ?? $request->getHost();
        $port = $appUrl['port'] ?? ($scheme === 'https' ? 443 : 80);
        $authority = $host.($port === 443 && $scheme === 'https' ? '' : ":{$port}");

        $request->headers->set('host', $authority);
        $request->headers->set('x-forwarded-host', $authority);
        $request->headers->set('x-forwarded-port', (string) $port);
        $request->headers->set('x-forwarded-proto', $scheme);
        $request->server->set('HTTP_HOST', $authority);
        $request->server->set('SERVER_NAME', $host);
        $request->server->set('SERVER_PORT', $port);
        $request->server->set('REQUEST_SCHEME', $scheme);
        $request->server->set('HTTPS', $scheme === 'https' ? 'on' : 'off');
    }
}
