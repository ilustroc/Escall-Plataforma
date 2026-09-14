<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        app(Vite::class)->useCspNonce();
        return $this->apply($request, $next($request));
    }
    public function apply(Request $request, Response $response): Response
    {
        $vite = app(Vite::class);
        $nonce = $vite->cspNonce() ?? bin2hex(random_bytes(16));
        $scripts = "script-src 'self' 'nonce-{$nonce}'";
        $styles = "style-src 'self' 'unsafe-inline'";
        $connect = "connect-src 'self'";
        // HMR únicamente en desarrollo y sólo destinos loopback.
        if (app()->environment('local') && $vite->isRunningHot()) {
            $url = trim((string)file_get_contents($vite->hotFile()));
            $parts = parse_url($url);
            if (in_array($parts['host'] ?? '', ['localhost','127.0.0.1','[::1]'], true)) {
                $origin = ($parts['scheme'] ?? 'http').'://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');
                $scripts .= ' '.$origin; $styles .= ' '.$origin;
                $connect .= ' '.$origin.' '.str_replace(['https://','http://'],['wss://','ws://'],$origin);
            }
        }
        $response->headers->set('Content-Security-Policy', implode('; ',[
            "default-src 'self'", "base-uri 'self'", "form-action 'self'", "frame-ancestors 'none'", "object-src 'none'",
            "img-src 'self' data: blob:", "font-src 'self' data:", $scripts, $styles, $connect,
        ]));
        foreach ([
            'X-Content-Type-Options'=>'nosniff','X-Frame-Options'=>'DENY','Referrer-Policy'=>'same-origin',
            'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()','Cache-Control'=>'no-store, private',
        ] as $name=>$value) $response->headers->set($name,$value);
        if ($request->isSecure()) $response->headers->set('Strict-Transport-Security','max-age=31536000');
        $response->headers->remove('X-Powered-By');
        if (!headers_sent()) header_remove('X-Powered-By');
        return $response;
    }
}