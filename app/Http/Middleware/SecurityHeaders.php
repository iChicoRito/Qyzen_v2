<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

// J1+J2: security response headers + a per-request CSP nonce. The nonce is shared to Blade as
// $cspNonce so inline <script> blocks can carry nonce="{{ $cspNonce }}" and satisfy the CSP.
// ponytail: one middleware for headers + CSP — they're set together on every web response.
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate the nonce BEFORE the view renders so Blade can use it.
        $nonce = Str::random(24);
        View::share('cspNonce', $nonce);
        // Task 33: make @vite-emitted script/style tags carry the same nonce (strict-dynamic CSP).
        Vite::useCspNonce($nonce);

        /** @var Response $response */
        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            'Content-Security-Policy' => $this->csp($nonce),
        ];

        // HSTS only over HTTPS (harmless header, but only meaningful on TLS).
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    private function csp(string $nonce): string
    {
        // Metronic ships inline styles + data: fonts/images; scripts are nonce-gated.
        // 'strict-dynamic' lets nonced bundle.js load its own chunks without host allowlisting.
        // NOTE: the Metronic Tailwind JS must be built for production (webpack mode=production,
        // no 'eval' devtool) or it will violate this CSP with an EvalError. See the template's
        // `npm run build:js -- --env production` — do NOT add 'unsafe-eval' here to work around it.
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'".$this->reverbOrigin(),
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }

    // Task 33: the Reverb WebSocket lives on its own host:port (a different origin), so the browser
    // needs it explicitly in connect-src. Built from config so dev (ws://localhost:8080) and prod
    // (wss://…) differ automatically; empty when Reverb isn't configured.
    private function reverbOrigin(): string
    {
        $options = config('broadcasting.connections.reverb.options');
        $host = $options['host'] ?? null;

        if (! $host) {
            return '';
        }

        $scheme = ($options['scheme'] ?? 'https') === 'https' ? 'wss' : 'ws';

        return ' '.$scheme.'://'.$host.':'.($options['port'] ?? 443);
    }
}
