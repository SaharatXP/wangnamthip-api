<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    public function createUser(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'tel' => 'required|string|unique:users,tel',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|confirmed',
            'role' => 'required|string'
        ]);
        $insert = User::create([
            'name' => $fields['name'],
            'tel' => $fields['tel'],
            'username' => $fields['username'],
            'password' => bcrypt($fields['password']),
            'role' => $fields['role'],
        ]);
        if (!$insert) {
            abort(500);
        } else {
            $response = ['message' => 'เพิ่มพนักงานสำเร็จ!'];
            return  response()->json($response, 201);
        }
    }
}
