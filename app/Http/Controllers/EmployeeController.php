<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmployeeInvitaion;
use Illuminate\Support\Facades\Password;
use App\Http\Helpers\GenerateEmployeeNumber;

require_once app_path('Http/Helpers/APIResponse.php');

class EmployeeController extends Controller
{
    
    /**
     * filtering the employees , returns on the basis of types, get all employees 
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA ,CA'(superAdmin , companyAdmin)
     * @route /employees
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
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

                // give the search results if the length is more than three characters
                if ($searchQuery && strlen($searchQuery) >= 3) {
                    $query->where(function ($query) use ($searchQuery) {
                        $query->where('first_name', 'like', '%' . $searchQuery . '%')
                            ->orWhere('last_name', 'like', '%' . $searchQuery . '%');
                    });
                }

                // result on the basis of filter
                if ($companyIdFilter) {
                    $query->where('company_id', $companyIdFilter);
                }

                // getting all the employees
                $employees = $query->get();

                return ok('Employees retrieved successfully', $employees);
            }

            return error('Unauthorized', [], 'forbidden');
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * storing the employee
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA ,CA'(superAdmin , companyAdmin)
     * @route /employee/create
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validation rules for employee creation
            $validator = Validator::make($request->all(), [
                'company_id'    => 'required|exists:companies,id',
                'first_name'    => 'required|string',
                'last_name'     => 'required|string',
                'email'         => 'required|email|unique:users,email',
                'address'       => 'required|string',
                'city'          => 'required|string',
                'dob'           => 'required|date',
                'joining_date'  => 'required|date',
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
           
                $user = User::create($request->only('company_id' , 'first_name' , 'last_name' , 'email' , 'address' , 'city ' , 'dob' , 'joining_date')+['password' => Hash::make('password'),'type' => 'E' ,'employee_number' =>GenerateEmployeeNumber::generateEmployeeNumber()]);


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
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * updating the particular employee
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA ,CA'(superAdmin , companyAdmin)
     * @route /employee/update/{id}
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            // Check user's role for authorization to update employee
            if (auth()->user()->type === 'SA' || (auth()->user()->type === 'CA' && auth()->user()->company_id === $user->company_id)) {
                // Validation rules for updating employee details
                $validator = Validator::make($request->all(), [
                    'first_name'    => 'required|string',
                    'last_name'     => 'required|string',
                    'email'         => 'required|email|unique:users,email,' . $id,
                    'address'       => 'required|string',
                    'city'          => 'required|string',
                    'dob'           => 'required|date',
                    'joining_date'  => 'required|date',
                ]);

                // If validation fails, return error response
                if ($validator->fails()) {
                    return error('Validation Failed. Please check the request attributes and try again.', $validator->errors()->toArray(), 'validation');
                }

                // Update employee details
               
                $user->update($request->only('first_name', 'last_name', 'email', 'address','city', 'dob', 'joining_date'));

                return ok('User updated successfully');
            } else {
                return error('Unauthorized', [], 'forbidden');
            }
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * showing the particular employee
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA ,CA'(superAdmin , companyAdmin)
     * @route /employee/{id}
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        try {
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
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * deleting the particular employee
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA ,CA'(superAdmin , companyAdmin)
     * @route /employee/{id}
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id, Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }
}
