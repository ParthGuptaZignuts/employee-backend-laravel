<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

require_once app_path('Http/Helpers/APIResponse.php');


class AuthenticationController extends Controller
{
    public function createUser(Request $request)
    {
        $validator = $this->validate($request, [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
        ]);

        $user = User::create([
            'first_name' => $validator["first_name"],
            'last_name' => $validator["last_name"],
            'email' => $validator["email"],
            'password' => Hash::make($validator["password"]),
        ]);

        return ok('User Created Successfully', $user);
    }

    public function loginUser(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email|exists:users',
            'password' => 'required|string',
        ], [
            'email.required'    => 'The email is required.',
            'email.email'       => 'Please enter a valid email address.',
            'email.exists'      => 'The specified email does not exist',
        ]);

        if (!Auth::attempt($request->only(['email', 'password']))) {
            return error('Email & Password does not match', [], 'unauthenticated');
        }

        $user = User::where('email', $request->email)->first();

        return ok('User Logged In Successfully', ['user' => $user, 'token' => $user->createToken("API TOKEN")->plainTextToken]);
    }

    public function getUser(Request $request)
    {
        $user = $request->user();

        return ok('User details retrieved successfully', $user);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return ok('User logged out successfully');
    }
}
