<?php

namespace App\Http\Controllers\API;

use App\Board;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

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
    public function show($board_id)
    {
    }
    public function storemember(Request $request, $board_id)
    {
    }
    public function deletemember($board_id, $member_id)
    {
    }
}
