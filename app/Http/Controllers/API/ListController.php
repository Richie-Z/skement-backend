<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Board_list;
use Illuminate\Support\Facades\Validator;

class ListController extends Controller
{
    public function store(Request $request, $board_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'invalid field', 'errors' => $validator->messages()], 422);
        }
        $list = new Board_list;
        $list->board_id = $board_id;
        $list->order = "0";
        $list->name = $request->name;
        $list->save();
        return response()->json(['message' => 'create list success'], 200);
    }
    public function update(Request $request, $board_id, $list_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'invalid field', 'errors' => $validator->messages()], 422);
        }
        Board_list::where('id', $list_id)
            ->where('board_id', $board_id)
            ->update([
                'board_id' => $board_id,
                'order' => "0",
                'name' => $request->name
            ]);
        return response()->json(['message' => 'update list success'], 200);
    }
    public function destroy($board_id, $list_id)
    {
        $list = Board_list::where('id', $list_id)->where('board_id', $board_id);
        $list->delete();
        return response()->json(['message' => 'delete list success'], 200);
    }
    public function right($board_id, $list_id)
    {
        $right = Board_list::where('id', $list_id)->where('board_id', $board_id)
            ->update(['order' => '4']);
        if ($right) {
            return response()->json(['message' => 'move right success'], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'error,please check board id and list id'], 422);
        }
    }
    public function left($board_id, $list_id)
    {
        $left = Board_list::where('id', $list_id)->where('board_id', $board_id)
            ->update(['order' => '2']);
        if ($left) {
            return response()->json(['message' => 'move left success'], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'error,please check board id and list id'], 422);
        }
    }
}
