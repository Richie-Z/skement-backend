<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Board_member;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|min:2|max:20',
            'last_name' => 'required|min:2|max:20',
            'username' => 'required|alpha-num|min:5|max:12|unique:users',
            'password' => 'required|min:5|max:12'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'messages' => 'invalid field',
                'errors' => $validator->messages()
            ], 422);
        }
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('token')->accessToken;

        return response()->json([$success], 200);
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'messages' => 'invalid login',
            ], 401);
        }
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            $user = Auth::user();
            $success = $user->createToken('token')->accessToken;

            return response()->json([
                'status' => true,
                'message' => 'Successfully logged in!',
                'token' => $success
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'messages' => 'invalid login',
            ], 401);
        }
    }
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }
    public function details()
    {
        $user = Auth::user();
        $member = Board_member::where('user_id', $user->id)->pluck('board_id')->toArray();
        if (!in_array('2', $member)) {
            return "false";
        }
        return "true";
    }
}
