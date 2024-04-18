<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Preference;
use App\Models\Company;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmployeeInvitaion;
use Illuminate\Support\Facades\Password;

require_once app_path('Http/Helpers/APIResponse.php');

class EmployeeController extends Controller
{
    // Method to generate a unique employee number
    public function generateEmployeeNumber(): string
    {
        // Retrieve the latest employee number preference
        $latestEmployeeNumberPref = Preference::where('code', 'EMP')->first();

        // If a preference exists, increment the latest employee number and save it
        if ($latestEmployeeNumberPref) {
            $latestEmployeeNumber = (int)$latestEmployeeNumberPref->value;
            $nextEmployeeNumber = 'EMP' . str_pad($latestEmployeeNumber + 1, 5, '0', STR_PAD_LEFT);
            $latestEmployeeNumberPref->value = $latestEmployeeNumber + 1;
            $latestEmployeeNumberPref->save();
        } else { // If no preference exists, start with EMP00001
            $nextEmployeeNumber = 'EMP00001';
            $latestEmployeeNumberPref = new Preference();
            $latestEmployeeNumberPref->code = 'EMP';
            $latestEmployeeNumberPref->value = 1;
            $latestEmployeeNumberPref->save();
        }

        return $nextEmployeeNumber;
    }

    // Method to retrieve employees based on search and company filters
    public function index(Request $request)
    {
        $user = auth()->user();

        // Check user's role for authorization
        if ($user->type === 'SA' || $user->type === 'CA') {
            $searchQuery = $request->input('search');
            $companyIdFilter = $request->input('search_filter');

            // Query to retrieve employees based on user's role and filters
            $query = User::with('company:id,name')->whereIn('type', ['CA', 'E']);

            if ($user->type === 'CA') {
                $query->where('company_id', $user->company_id);
            }

            if ($searchQuery && strlen($searchQuery) >= 3) {
                $query->where(function ($query) use ($searchQuery) {
                    $query->where('first_name', 'like', '%' . $searchQuery . '%')
                        ->orWhere('last_name', 'like', '%' . $searchQuery . '%');
                });
            }

            if ($companyIdFilter) {
                $query->where('company_id', $companyIdFilter);
            }

            $employees = $query->get();

            return ok('Employees retrieved successfully', $employees);
        }

        return error('Unauthorized', [], 'forbidden');
    }

    // Method to store a new employee
    public function store(Request $request)
    {
        // Validation rules for employee creation
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'address' => 'required|string',
            'city' => 'required|string',
            'dob' => 'required|date',
            'joining_date' => 'required|date',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return error('Validation Failed. Please check the request attributes and try again.', $validator->errors()->toArray(), 'validation');
        }

        // Find the company by ID
        $company = Company::find($request->company_id);
        if (!$company) {
            return error('Company not found', [], 'notfound');
        }

        // Check user's role for authorization to create employee
        if (auth()->user()->type === 'SA' || (auth()->user()->type === 'CA' && auth()->user()->company_id === $request->company_id)) {
            // Create new user with employee role
            $user = new User();
            $user->type = 'E';
            $user->company_id = $request->company_id;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->password = Hash::make("password");
            $user->address = $request->address;
            $user->city = $request->city;
            $user->dob = $request->dob;
            $user->joining_date = $request->joining_date;
            $user->employee_number = $this->generateEmployeeNumber();
            $user->save();

            // Generate password reset token
            $token = Password::createToken($user);
            $resetLink = url('http://localhost:5173/resetPassword/' . $token);

            // Send invitation email to the new employee
            Mail::to($user['email'])->send(new EmployeeInvitaion(
                $user['first_name'],
                $user['last_name'],
                $user['email'],
                $user['employee_number'],
                $company['name'],
                $company['website'],
                $resetLink
            ));

            return ok('User created successfully');
        } else {
            return error('Only users with type SA can create employees', [], 'forbidden');
        }
    }

    // Method to update an existing employee
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return error('User not found', [], 'notfound');
        }

        // Check user's role for authorization to update employee
        if (auth()->user()->type === 'SA' || (auth()->user()->type === 'CA' && auth()->user()->company_id === $user->company_id)) {
            // Validation rules for updating employee details
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email|unique:users,email,' . $id,
                'address' => 'required|string',
                'city' => 'required|string',
                'dob' => 'required|date',
                'joining_date' => 'required|date',
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return error('Validation Failed. Please check the request attributes and try again.', $validator->errors()->toArray(), 'validation');
            }

            // Update employee details
            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'dob' => $request->dob,
                'joining_date' => $request->joining_date,
            ]);

            return ok('User updated successfully');
        } else {
            return error('Unauthorized', [], 'forbidden');
        }
    }

    // Method to retrieve details of a specific employee
    public function show(string $id)
    {
        $user = User::with('company:id,name')->find($id);

        if (!$user) {
            return error('User not found', [], 'notfound');
        }

        // Check user's role for authorization to view employee details
        if (auth()->user()->type === 'SA' || (auth()->user()->type === 'CA' && auth()->user()->company_id === $user->company_id)) {
            return ok('User retrieved successfully', $user);
        } else {
            return error('Unauthorized', [], 'forbidden');
        }
    }

    // Method to delete an employee
    public function destroy(string $id, Request $request)
{
    $user = User::find($id);

    if (!$user) {
        return error('User not found', [], 'notfound');
    }

    // Check user's role for authorization to delete employee
    if (auth()->user()->type === 'SA') {
        // Super admin can delete any user except other super admins and company admins
        if ($user->type === 'SA' || $user->type === 'CA') {
            return error('Super admin cannot delete another super admin or company admin', [], 'forbidden');
        }
    } elseif (auth()->user()->type === 'CA') {
        // Company admin cannot delete themselves or other company admins
        if ($user->type === 'CA') {
            return error('Company admin cannot be deleted or soft deleted', [], 'forbidden');
        }
    }

    // Proceed with deletion
    if ($request->has('permanent_delete') && $request->boolean('permanent_delete')) {
        $user->forceDelete();
        return ok('User permanently deleted successfully');
    } else {
        $user->delete();
        return ok('User soft deleted successfully');
    }
}

}
