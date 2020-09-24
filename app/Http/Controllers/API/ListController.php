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
    }
    public function destroy($board_id, $list_id)
    {
    }
    public function right(Request $request, $board_id, $list_id)
    {
    }
    public function left(Request $request, $board_id, $list_id)
    {
    }
}
