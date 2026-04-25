<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Unauthorized',
                'message_code' => 'unauthorized',
                'data' => [],
                'errors' => [],
                'exception' => ''
            ], 401);
        }

        if (!$user->status) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Account suspended',
                'message_code' => 'suspended',
                'data' => [],
                'errors' => [],
                'exception' => ''
            ], 403);
        }

        if (!$user->is_email_verified || !$user->is_mobile_verified) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Please verify email and mobile',
                'message_code' => 'verification_required',
                'data' => [
                    'is_email_verified' => $user->is_email_verified,
                    'is_mobile_verified' => $user->is_mobile_verified
                ],
                'errors' => [],
                'exception' => ''
            ], 403);
        }
    return $next($request);
    }
}
