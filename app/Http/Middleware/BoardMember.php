<?php

namespace App\Http\Middleware;

use Closure;
use App\Board_member;
use App\Board;
use Illuminate\Support\Facades\Auth;

class BoardMember
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
        $board_id = $request->route('board_id');
        $user = Auth::user();
        $member = Board_member::where('user_id', $user->id);
        if ($member->count() == 0) {
            return response()->json(['message' => 'Unauthorized, this user doesn`t have a member on any board'], 401);
        }
        $validate = Board::where('id', $member->first()->board_id)->count();
        if ($board_id != $member->first()->board_id) {
            return response()->json(['message' => 'Unauthorized, you are not a board member'], 401);
        }
        return $next($request);
    }
}
