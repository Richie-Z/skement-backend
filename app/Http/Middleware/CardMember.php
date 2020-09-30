<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Board_member;
use App\Board_list;
use App\Card;

class CardMember
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
        $card_id = $request->route('card_id');
        $user = Auth::user();
        $member = Board_member::where('user_id', $user->id)->pluck('board_id')->toArray();
        $list = Board_list::whereIn('board_id', $member)->pluck('id')->toArray();
        $card = Card::whereIn('list_id', $list)->where('id', $card_id)->count();
        if ($card == 0) {
            return response()->json(['message' => 'Unauthorized, you are not a board member'], 401);
        }
        return $next($request);
    }
}
