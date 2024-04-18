<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\Company;
use App\Models\User;
use App\Models\JobDescription;

require_once app_path('Http/Helpers/APIResponse.php');

class AuthenticationController extends Controller
{
    public function createUser(Request $request)
    {
        // checking validation
        $validator = $this->validate($request, [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
        ]);

        // creating user
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
    }

    public function getUser(Request $request)
    {
        // fetching the user
        $user = $request->user();
        return ok('User details retrieved successfully', $user);
    }

    public function logout(Request $request)
    {
        // deleting the user with token 
        $request->user()->tokens()->delete();
        return ok('User logged out successfully');
    }

    public function resetPassword(Request $request)
    {
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
    }

        // Method to retrieve statistics based on user type
        public function getStatistics(Request $request)
        {
            // If the user is a super admin
            if ($request->user()->type === 'SA') {
                // Retrieve total counts of companies, company admins, employees, and job descriptions
                $totalCompanies = Company::count();
                $totalCompanyAdmin = User::whereIn('type', ['CA'])->count();
                $totalEmployees = User::whereIn('type', ['E'])->count();
                $totalJobs = JobDescription::count();
    
                // Return the statistics as JSON response
                return response()->json([
                    'total_companies' => $totalCompanies,
                    'total_employees' => $totalEmployees,
                    'total_ca' => $totalCompanyAdmin,
                    'total_jobs' => $totalJobs,
                ]);
            }
            // If the user is a company admin
            elseif ($request->user()->type === 'CA') {
                // Retrieve company ID of the logged-in company admin
                $companyId = $request->user()->company_id;
    
                // Retrieve total counts of employees and job descriptions for the company
                $totalEmployees = User::where('type', 'E')->where('company_id', $companyId)->count();
                $totalJobs = JobDescription::whereHas('company', function ($query) use ($companyId) {
                    $query->where('id', $companyId);
                })->count();
    
                // Return the statistics for the company as JSON response
                return response()->json([
                    'total_employees' => $totalEmployees,
                    'total_jobs' => $totalJobs,
                ]);
            }
            // If the user is neither a super admin nor a company admin
            else {
                // Return unauthorized error
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }
}
