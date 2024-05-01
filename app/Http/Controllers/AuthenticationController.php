<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;

require_once app_path('Http/Helpers/APIResponse.php');

class AuthenticationController extends Controller
{
    /**
     * creating the users for registration
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @route /register
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function createUser(Request $request)
    {
        try {
            // checking validation
            $validator = $this->validate($request, [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email|unique:users',
                'phone' => 'required|string',
                'dob' => 'required|date',
                'city' => 'required|string',
                'password' => 'required|string|confirmed',
            ]);

            // creating user
            $user = User::create([
                'first_name' => $validator["first_name"],
                'last_name' => $validator["last_name"],
                'email' => $validator["email"],
                'phone' => $validator["phone"],
                'dob' => $validator["dob"],
                'city' => $validator["city"],
                'password' => Hash::make($validator["password"]),
            ]);

            return ok('User Created Successfully', $user);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * login in the users
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @route /login
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function loginUser(Request $request)
    {
        try {


            // checking required parameters
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
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * geting all the users
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,
     * @param \Illuminate\Http\Request $request
     * @route /user
     * @return \Illuminate\Http\Response
     */
    public function getUser(Request $request)
    {
        try {
            // fetching the user
            $user = $request->user();
            return ok('User details retrieved successfully', $user);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * login out the users
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,
     * @param \Illuminate\Http\Request $request
     * @route /logout
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        try {
            // Check if the user is authenticated
            if ($request->user()) {
                // Deleting the user's tokens
                $request->user()->tokens()->delete();
                return ok('User logged out successfully');
            } else {
                // If user is not authenticated, return an error
                return error('User not authenticated', [], 'unauthenticated');
            }
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }
    /**
     * resting the password of the users
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @route /password/reset
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function resetPassword(Request $request)
    {
        try {
            // checking validation 
            $request->validate([
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|confirmed',
            ]);

            // resetting the password
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->password = Hash::make($password);
                    $user->save();
                    event(new PasswordReset($user));
                }
            );

            return $status === Password::PASSWORD_RESET
                ? ok('Password reset successfully.')
                : error('Invalid token or email. Please request a new reset link.', [], 'error');
                
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }
}
