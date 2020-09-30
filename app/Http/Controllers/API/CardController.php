<?php

namespace App\Http\Controllers\API;

use App\Board;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Card;
use App\Board_list;

class CardController extends Controller
{
    public function store(Request $request, $board_id, $list_id)
    {
        $validate = Validator::make($request->all(), [
            'task' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => 'invalid field', 'errors' => $validate->messages()], 422);
        }
        $card = new Card([
            'list_id' => $list_id,
            'order' => "0",
            'task' => $request->task
        ]);
        $list = Board_List::where('board_id', $board_id)->where('id', $list_id)->first();
        $list->card()->save($card);
        return response()->json(['message' => 'create card success'], 200);
    }
    public function update(Request $request, $board_id, $list_id, $card_id)
    {
        $validate = Validator::make($request->all(), [
            'task' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => 'invalid field', 'errors' => $validate->messages()], 422);
        }
        $list = Board_List::where('board_id', $board_id)->where('id', $list_id)->first();
        $list->card()->find($card_id)->update([
            'list_id' => $list_id,
            'order' => "0",
            'task' => $request->task
        ]);
        return response()->json(['message' => 'update card success'], 200);
    }
    public function destroy($board_id, $list_id, $card_id)
    {
        $list = Board_List::where('board_id', $board_id)->where('id', $list_id)->first();
        $list->card()->find($card_id)->delete();
        return response()->json(['message' => 'delete card success'], 200);
    }
    public function up($card_id)
    {
        $card = Card::find($card_id);
        $card->order = "1";
        $card->save();
        if ($card) {
            return response()->json(['message' => 'move card up success'], 200);
        }
    }
    public function down($card_id)
    {
        $card = Card::find($card_id);
        $card->order = "3";
        $card->save();
        if ($card) {
            return response()->json(['message' => 'move card down success'], 200);
        }
    }
    public function move($card_id, $list_id)
    {
        $card = Card::find($card_id);
        $card->list_id = $list_id;
        $card->save();
        if ($card) {
            return response()->json(['message' => 'move card to another list success'], 200);
        }
    }
}
