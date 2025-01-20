<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('Checking Google authentication', [
            'session_email' => session('user_email'),
            'path' => $request->path()
        ]);

        if (!session('user_email')) {
            Log::warning('No user email in session, redirecting to login');
            return redirect()->route('login');
        }

        return $next($request);
    }
} 