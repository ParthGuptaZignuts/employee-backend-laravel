<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use App\Models\Preference;
use Illuminate\Support\Facades\Hash;

require_once app_path('Http/Helpers/APIResponse.php');

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::all();
        return ok('Companies retrieved successfully', $companies);
    }

    public function generateEmployeeNumber(): string
{
    $latestEmployee = User::latest()->first();

    if (!$latestEmployee) {
        return '00000'; // Start with 00000 if no employee exists
    } else {
        $latestEmployeeNumber = (int) $latestEmployee->emp_number;
        $nextEmployeeNumber = str_pad($latestEmployeeNumber + 1, 5, '0', STR_PAD_LEFT);
        $latestEmployee->employee_number = $nextEmployeeNumber;
        $latestEmployee->save();
        return $nextEmployeeNumber;
    }
}

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:64',
            'email' => 'required|email|max:128',
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

        // Create the company
        $company = new Company();
        $company->fill($request->all());
        $company->save();

        // Create the admin user
        $admin = new User();
        $admin->fill($request->input('admin'));
        $admin->type = "CA";
        $admin->password = Hash::make($request->input('admin.password')); // Hash the password
        $admin->company_id = $company->id; // Assign the company ID to the admin
        $admin->save();

        // Generate and save employee number for admin
        $admin->employee_number = $this->generateEmployeeNumber();
        $admin->save();

        $preferences = Preference::firstOrNew(['code' => $admin->id]);
        $preferences->value = $admin->employee_number;
        $preferences->save();

        return ok('Company created successfully', $company, 201);
    }


    public function update(Request $request, string $id)
    {
        $validator = $request->validate([
            'name' => 'required|string|max:64',
            'email' => 'required|email|max:128' . $id,
            'website' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:A,I',
            'admin.first_name' => 'required|string|max:255',
            'admin.last_name' => 'required|string|max:255',
            'admin.email' => 'required|email|unique:users,email',
            'admin.address' => 'required|string|max:255',
            'admin.city' => 'required|string|max:255',
            'admin.dob' => 'required|date',
        ]);

        $company = Company::findOrFail($id);
        $company->fill($validator);

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('public/logos');
            $company->logo_url = basename($logoPath);
        }

        $company->save();

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
        $company = Company::with('admin')->find($companyId);

        if (!$company) {
            return error('Company not found', [], 'notfound');
        }

        return ok('Company retrieved successfully', $company);
    }

    public function destroy(string $id)
    {
        $company = Company::findOrFail($id);
        $admin = $company->admin;
        if ($admin) {
            $admin->delete();
        }
        $company->delete();
        return ok('Company and its associated admin deleted successfully');
    }
}
