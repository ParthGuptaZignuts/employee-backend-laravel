<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use App\Mail\InvitationMail;
use Illuminate\Support\Facades\Auth;
use App\Http\Helpers\GenerateEmployeeNumber;

require_once app_path('Http/Helpers/APIResponse.php');
class CompanyController extends Controller
{
    /**
     * showing all the companies and showing if the search and filter request is there from frontend
     * filtering the companies , returns on the basis of status, get all companies 
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA'(superAdmin)
     * @route /companies
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // checking whether search or status is there or not
            $searchQuery = $request->input('search');
            $status = $request->input('status');

            // if search is there
            $query = Company::query();

            // search filter provided
            if ($searchQuery && strlen($searchQuery) >= 3) {
                $query->where('name', 'like', '%' . $searchQuery . '%');
            }

            // status filter provided
            if ($status && in_array($status, ['A', 'I'])) {
                $query->where('status', $status);
            }

            // Get the result
            $companies = $query->get();

            return ok('Companies retrieved successfully ', $companies);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * create a new company 
     * storing all the companies
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA'(superAdmin)
     * @route /create
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        // validate the request parameters for company and company admin 
        $request->validate([
            'name'             => 'required|string|max:64',
            'email'            => 'required|email|max:128',
            'website'          => 'nullable|string|max:255',
            'address'          => 'required|string|max:255',
            'logo'             => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'status'           => 'required|in:A,I',
            'admin.first_name' => 'required|string|max:255',
            'admin.last_name'  => 'required|string|max:255',
            'admin.email'      => 'required|email',
            'admin.address'    => 'required|string|max:255',
            'admin.city'       => 'required|string|max:255',
            'admin.dob'        => 'required|date',
        ]);

        // creating new company 
        $company = Company::create($request->only('name', 'email', 'website', 'address', 'status'));
        // storing company logo if there is one
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('public/logos');
            $company->logo = basename($logoPath);
            $company->save();
        }

        // creating company admin
        $admin = new User();
        $admin->fill($request->only('admin'));
        $admin->type = "CA";
        $admin->password = Hash::make("password");
        $admin->company_id = $company->id;
        $admin->save();

        // generating employee number for company admin
        $admin->employee_number = GenerateEmployeeNumber::generateEmployeeNumber();
        $admin->save();

        // generating token and reset password link
        $token = Password::createToken($admin);

        $resetLink = config('constant.frontend_url') . $token;


        // sending invitation email to company admin
        Mail::to($admin->email)->send(new InvitationMail(
            $admin->first_name,
            $admin->last_name,
            $admin->email,
            $company->name,
            $company->website,
            $resetLink
        ));

        return ok('Company created successfully', $company, 201);
    }

    /**
     * updat the company
     * updating the specifics companies
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA'(superAdmin)
     * @param \Illuminate\Http\Request $request
     * @param string id
     * @route /companies/{id}
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, string $id)
    {
        try {
            // validate the request parameters for company and company admin
            $validator = $request->validate([
                'name'             => 'required|string|max:64',
                'email'            => 'required|email|max:128' . $id,
                'website'          => 'nullable|string|max:255',
                'address'          => 'required|string|max:255',
                'logo'             => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'status'           => 'required|in:A,I',
                'admin.first_name' => 'required|string|max:255',
                'admin.last_name'  => 'required|string|max:255',
                'admin.email'      => 'required|email',
                'admin.address'    => 'required|string|max:255',
                'admin.city'       => 'required|string|max:255',
                'admin.dob'        => 'required|date',
            ]);

            $company = Company::findOrFail($id);
            $company->fill($validator);

            // storing company logo if there is one for updating
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('public/logos');
                $company->logo = basename($logoPath);
            }

            $company->save();

            // updating company admin
            if ($request->has('admin')) {
                $adminData = $validator['admin'];
                $admin = $company->admin;
                if ($admin) {
                    $admin->update($adminData);
                } else {
                    // If admin doesn't exist, create a new one
                    $admin = new User();
                    $admin->fill($adminData);
                    $admin->type = "CA";
                    $admin->password = Hash::make($adminData['password']);
                    $admin->company_id = $company->id;
                    $admin->save();
                }
            }

            return ok('Company updated successfully', $company, 200);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * in drawer show particular company information
     * showing the particular company
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA'(superAdmin)
     * @param string company id
     * @route /companies/{id}
     * @return \Illuminate\Http\Response
     */

    public function show($companyId)
    {
        try {
            // get company information with admin
            $company = Company::with('admin')->findOrFail($companyId);

            if (!$company) {
                return error('Company not found', [], 'notfound');
            }

            return ok('Company retrieved successfully', $company);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * soft delete and permanently delete particular company
     * deleting (soft delete hard delete) the particular company
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA'(superAdmin)
     * @route /companies/delete/{id}
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */

    public function destroy(string $id, Request $request)
    {
        try {
            // Find company by ID
            $company = Company::withTrashed()->findOrFail($id);

            // Delete company admin
            $admin = $company->admin;
            if ($admin) {
                if ($request->has('force_delete') && $request->force_delete) {
                    $admin->forceDelete();
                } else {
                    $admin->delete();
                }
            }
            // Delete employees and job descriptions 
            if ($request->has('force_delete') && $request->force_delete) {
                $company->employees()->forceDelete();
                $company->jobDescriptions()->forceDelete();
                $company->forceDelete();
            } else {
                $company->employees()->delete(); // Soft delete employees
                $company->jobDescriptions()->delete(); // Soft delete job descriptions
                $company->delete(); // Soft delete company
            }

            return ok('Company, its associated admin, and job descriptions deleted successfully');
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * this is getting all the company list,used in employee page filtering (Company admin and employee )according to company name
     * getting all the companies
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA,CA'(superAdmin , companyAdmin)
     * @route /getallcompanies
     * @return \Illuminate\Http\Response
     */

    public function getAllCompanies()
    {
        try {
            $user = Auth::user();

            // Check if the user is a Super Admin (SA)
            if ($user->type === 'SA') {
                $companies = Company::select('id', 'name')->get();
            } elseif ($user->type === 'CA') {
                // If the user is a Company Admin (CA)
                $companies = Company::select('id', 'name')->where('id', $user->company_id)->get();
            } else {
                // Handle other user types if necessary
                return error('Unauthorized access', [], 'unauthorized', 403);
            }

            return ok('Companies retrieved successfully', $companies);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }
    /**
     * getting all the companies with logos (with token), getting 4 companies with logos (without token) , this is for candidates pages which is in nuxt 
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @route /companyWithLogo
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function companyWithLogo(Request $request)
    {
        try {
            $token = $request->header('Authorization');

            if ($token) {
                // If token is provided, get all companies with logo
                $companies = Company::select('name', 'logo')->get();
                return ok('All companies with logos retrieved successfully', $companies);
            } else {
                // If no token, limit the result to 4 companies with logo
                $companies = Company::select('name', 'logo')->limit(4)->get();
                return ok('Limited companies with logos retrieved successfully', $companies);
            }
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }
}
