<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        if (! $user || $user->status !== User::ACTIVE || ($request->session()->has('auth_key') && ! hash_equals($user->auth_key, $request->session()->get('auth_key')))) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }
        abort_unless(in_array((string) $user->rol_id, $roles, true), 403);

        return $next($request);
    }
}
