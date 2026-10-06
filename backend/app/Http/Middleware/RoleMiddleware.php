<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để thực hiện thao tác này.'
            ], 401);
        }

        if (isset($user->is_active) && ! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn đã bị vô hiệu hóa.'
            ], 403);
        }

        if (! empty($roles)) {
            $parsedRoles = [];
            foreach ($roles as $r) {
                foreach (explode(',', $r) as $item) {
                    $item = trim($item);
                    if ($item !== '') {
                        $parsedRoles[] = $item;
                    }
                }
            }

            $allowed = $parsedRoles;
            // Cho phép can_bo tương đương lecturer
            if (in_array('lecturer', $parsedRoles) && ! in_array('can_bo', $allowed)) {
                $allowed[] = 'can_bo';
            }
            if (in_array('can_bo', $parsedRoles) && ! in_array('lecturer', $allowed)) {
                $allowed[] = 'lecturer';
            }

            if (! in_array($user->role, $allowed)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập vào chức năng này.'
                ], 403);
            }
        }

        return $next($request);
    }
}
