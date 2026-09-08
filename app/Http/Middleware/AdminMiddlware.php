<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddlware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. التأكد من أن المستخدم قام بتسجيل الدخول
        // 2. التحقق مما إذا كان يمتلك صلاحية أدمن (افترضنا وجود حقل is_admin أو role)
        if (auth()->check() && (auth()->user()->is_admin || auth()->user()->role === 'admin')) {
            return $next($request);
        }

        // إذا كان الطلب عبارة عن API (يكافئ JSON)
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthorized access. Admin privileges required.'
            ], 403);
        }

        // إذا كان الطلب من المتصفح العادي
        abort(403, 'Unauthorized access.');
    }
}
