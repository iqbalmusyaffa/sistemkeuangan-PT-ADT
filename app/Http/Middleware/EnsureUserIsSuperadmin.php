<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperadmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Jika user tidak login atau bukan superadmin atau status tidak aktif
        if (!$user || $user->role !== 'superadmin' || $user->status !== 'active') {
            return response()->json([
                'message' => 'Unauthorized. Only active superadmin can access this route.'
            ], 403);
        }

        return $next($request);
    }
}
