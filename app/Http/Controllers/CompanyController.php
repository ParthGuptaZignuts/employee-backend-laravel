<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use App\Models\Preference;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use App\Mail\InvitationMail;
use Illuminate\Support\Facades\Auth;

require_once app_path('Http/Helpers/APIResponse.php');

class CompanyController extends Controller
{
    // generates the employeement number for Company Admin
    public function generateEmployeeNumber(): string
    {
        $latestEmployeeNumberPref = Preference::where('code', 'EMP')->first();

        if ($latestEmployeeNumberPref) {
            $latestEmployeeNumber = (int)$latestEmployeeNumberPref->value;
            $nextEmployeeNumber = 'EMP' . str_pad($latestEmployeeNumber + 1, 5, '0', STR_PAD_LEFT);
            $latestEmployeeNumberPref->value = $latestEmployeeNumber + 1;
            $latestEmployeeNumberPref->save();
        } else {
            $nextEmployeeNumber = 'EMP00001';
            $latestEmployeeNumberPref = new Preference();
            $latestEmployeeNumberPref->code = 'EMP';
            $latestEmployeeNumberPref->value = 1;
            $latestEmployeeNumberPref->save();
        }

        return $nextEmployeeNumber;
    }


    public function index(Request $request)
    {
        $searchQuery = $request->input('search');
        $status = $request->input('status');

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

        return ok('Companies retrieved successfully', $companies);
    }

    public function store(Request $request)
    {
        // validate the request parameters for company and company admin 
        $request->validate([
            'name' => 'required|string|max:64',
            'email' => 'required|email|max:128',
            'website' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'status' => 'required|in:A,I',
            'admin.first_name' => 'required|string|max:255',
            'admin.last_name' => 'required|string|max:255',
            'admin.email' => 'required|email',
            'admin.address' => 'required|string|max:255',
            'admin.city' => 'required|string|max:255',
            'admin.dob' => 'required|date',
        ]);

        // creating new company 
        $company = new Company();
        $company->fill($request->except('logo'));
        $company->save();

        // storing company logo if there is one
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('public/logos');
            $company->logo = basename($logoPath);
            $company->save();
        }

        // creating company admin
        $admin = new User();
        $admin->fill($request->input('admin'));
        $admin->type = "CA";
        $admin->password = Hash::make("password");
        $admin->company_id = $company->id;
        $admin->save();

        // generating employee number for company admin
        $admin->employee_number = $this->generateEmployeeNumber();
        $admin->save();

        // generating token and reset password link
        $token = Password::createToken($admin);
        $resetLink = url('http://localhost:5173/resetPassword/' . $token);

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


    public function update(Request $request, string $id)
    {
        // validate the request parameters for company and company admin
        $validator = $request->validate([
            'name' => 'required|string|max:64',
            'email' => 'required|email|max:128' . $id,
            'website' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:A,I',
            'admin.first_name' => 'required|string|max:255',
            'admin.last_name' => 'required|string|max:255',
            'admin.email' => 'required|email',
            'admin.address' => 'required|string|max:255',
            'admin.city' => 'required|string|max:255',
            'admin.dob' => 'required|date',
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
    }

    public function show($companyId)
    {
        // get company information with admin
        $company = Company::with('admin')->find($companyId);

        if (!$company) {
            return error('Company not found', [], 'notfound');
        }

        return ok('Company retrieved successfully', $company);
    }

    // with job description deleted successfully
    public function destroy(string $id, Request $request)
    {
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
    }



    public function getAllCompanies()
    {
        $user = Auth::user();

        // Check if the user is a Super Admin (SA)
        if ($user->type === 'SA') {
            $companies = Company::select('id', 'name')->get();
        }
        // Check if the user is a Company Admin (CA)
        elseif ($user->type === 'CA') {
            $companies = Company::select('id', 'name')->where('id', $user->company_id)->get();
        }
        // Handle other user types if necessary
        return response()->json($companies);
    }
}
