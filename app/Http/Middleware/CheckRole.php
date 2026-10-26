<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Usage: ->middleware('role:super_admin,finance')
     *
     * Passes if the authenticated user's admin_role is one of the
     * listed roles. Legacy admins (is_admin=true, admin_role=null)
     * are treated as super_admin.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->is_admin) {
            abort(403, 'Admin access required.');
        }

        $userRole = $user->admin_role ?? 'super_admin';

        if (!empty($roles) && !in_array($userRole, $roles)) {
            abort(403, 'Your role (' . $userRole . ') does not have permission for this section.');
        }

        return $next($request);
    }
}
