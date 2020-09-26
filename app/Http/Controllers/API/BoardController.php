<?php

namespace App\Http\Controllers\API;

use App\Board;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Board_member;

class BoardController extends Controller
{
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => 'invalid field', 'errors' => $validate->messages()], 422);
        }
        $board = new Board;
        $board->creator_id = Auth::user()->id;
        $board->name = $request->name;
        $board->save();
        // Board::create([
        //     'creator_id' => Auth::user()->id,
        //     'name' => $request->name,
        // ]);
        return response()->json(['message' => 'create board success'], 200);
    }
    public function update(Request $request, $board_id)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => 'invalid field', 'errors' => $validate->messages()], 422);
        }
        $board = Board::find($board_id);
        $board->creator_id = Auth::user()->id;
        $board->name = $request->name;
        $board->save();
        return response()->json(['message' => 'update board success'], 200);
    }
    public function destroy($board_id)
    {
        $board = Board::find($board_id);
        $board->delete();
        return response()->json(['message' => 'delete board success'], 200);
    }
    public function index()
    {
        $board = Board::all();
        return response()->json(['data' => $board], 200);
    }
    public function show($board_id, Board $board)
    {
        return $board->where('id', $board_id)->with(['user', 'list'])->get();
    }
    public function storemember(Request $request, $board_id)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'invalid field', 'errors' => $validator->messages()], 422);
        }
        $validate = User::where('username', $request->username)->count();
        if ($validate == 0) {
            return response()->json(['message' => 'user did not exist'], 422);
        }
        $user = User::where('username', $request->username)->first();
        $member = new Board_member;
        $member->board_id = $board_id;
        $member->user_id = $user->id;
        $member->save();
        return response()->json(['message' => 'add member success'], 200);
    }
    public function deletemember($board_id, $member_id)
    {
        $member = Board_member::where('board_id', $board_id)->where('id', $member_id)->delete();
        if ($member) {
            return response()->json(['message' => 'delete member success'], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'error member not found ,check board id or member id'], 422);
        }
    }
}
