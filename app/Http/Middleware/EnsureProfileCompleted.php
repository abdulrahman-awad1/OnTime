<?php

namespace App\Http\Middleware;

use App\trait\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->returnError('401', 'Unauthenticated', 401);
        }

        if ($user->role === 'patient' && (!$user->profile_completed || empty($user->name))) {
            return $this->returnError(
                'E_PROFILE_INCOMPLETE',
                'يرجى استكمال بيانات الملف الشخصي (الاسم، تاريخ الميلاد، النوع، والعنوان) قبل إتمام الحجز أو الدفع.',
                403
            );
        }

        return $next($request);
    }
}
