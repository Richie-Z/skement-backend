<?php

namespace App\Http\Middleware;

use Closure;
use App\Board_member as Bmember;
use App\User;
use Illuminate\Support\Facades\Auth;

class Member
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $userid = Auth::user();
        $member = Bmember::where('user_id', $userid->id)->count();
        if ($member == 0) {
            return response()->json(['message' => 'Unauthorized, you are not a member'], 401);
        }
        return $next($request);
    }
}
