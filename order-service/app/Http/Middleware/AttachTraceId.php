<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OrderHub\Shared\Observability\TraceHeaders;
use Symfony\Component\HttpFoundation\Response;

class AttachTraceId
{
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = TraceHeaders::resolveTraceId(
            $request->header('X-Trace-Id'),
            $request->header('traceparent')
        );
        $traceparent = (string) ($request->header('traceparent') ?: TraceHeaders::traceparentFromTraceId($traceId));
        $request->attributes->set('trace_id', $traceId);
        $request->attributes->set('traceparent', $traceparent);

        $response = $next($request);
        $response->headers->set('X-Trace-Id', $traceId);
        $response->headers->set('traceparent', $traceparent);

        return $response;
    }
}
