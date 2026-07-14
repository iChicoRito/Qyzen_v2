<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AjaxFormResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->handles($request)) {
            return $next($request);
        }

        try {
            $response = $next($request);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please correct the highlighted fields.',
                'errors' => $exception->errors(),
            ], 422);
        }

        if (! $response instanceof RedirectResponse) {
            return $response;
        }

        if ($request->session()->has('errors')) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Please correct the highlighted fields.',
                'errors' => $request->session()->get('errors')->getBag('default')->toArray(),
            ], 422);
        }

        $error = $request->session()->get('error');
        $status = $request->session()->get('status');

        return new JsonResponse([
            'status' => $error ? 'error' : 'success',
            'message' => $error ?: ($status ?: 'Saved.'),
            'redirect' => $response->getTargetUrl(),
        ]);
    }

    private function handles(Request $request): bool
    {
        return $request->ajax()
            && ! $request->isMethod('GET')
            && ($request->is('admin/*') || $request->is('educator/*'));
    }
}
