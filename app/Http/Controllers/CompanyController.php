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


require_once app_path('Http/Helpers/APIResponse.php');

class CompanyController extends Controller
{   
   
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


    public function index()
    {
        $companies = Company::all();
        return ok('Companies retrieved successfully', $companies);
    }

    


    public function store(Request $request)
    {
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


        $company = new Company();
        $company->fill($request->except('logo'));
        $company->save();

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('public/logos');
            $company->logo = basename($logoPath);
            $company->save();
        }

        $admin = new User();
        $admin->fill($request->input('admin'));
        $admin->type = "CA";
        $admin->password = Hash::make("password");
        $admin->company_id = $company->id;
        $admin->save();

        $admin->employee_number = $this->generateEmployeeNumber();
        $admin->save();

        $token = Password::createToken($admin);
        $resetLink = url('http://localhost:5173/resetPassword/' . $token);

        // Mail::to($admin['email'])->send(new InvitationMail($admin['first_name'],$admin['last_name'],$admin['email'],$company['name'],$company['website']));

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

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('public/logos');
            $company->logo = basename($logoPath);
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

    // public function destroy(string $id, Request $request)
    // {
    //     $company = Company::withTrashed()->findOrFail($id);
    //     $admin = $company->admin;

    //     if ($admin) {
    //         if ($request->has('hard_delete') && $request->hard_delete) {
    //             $admin->forceDelete(); // Hard delete admin user
    //         } else {
    //             $admin->delete(); // Soft delete admin user
    //         }
    //     }

    //     if ($request->has('hard_delete') && $request->hard_delete) {
    //         $company->forceDelete(); // Hard delete company
    //     } else {
    //         $company->delete(); // Soft delete company
    //     }

    //     return ok('Company and its associated admin deleted successfully');
    // }

    public function destroy(string $id, Request $request)
{
    $company = Company::withTrashed()->findOrFail($id);

    $admin = $company->admin;
    if ($admin) {
        if ($request->has('hard_delete') && $request->hard_delete) {
            $admin->forceDelete();
        } else {
            $admin->delete();
        }
    }

    if ($request->has('hard_delete') && $request->hard_delete) {
        $company->jobDescriptions()->forceDelete();
        $company->forceDelete(); 
    } else {
        $company->jobDescriptions()->delete(); // Soft delete job descriptions
        $company->delete(); // Soft delete company
    }

    return ok('Company, its associated admin, and job descriptions deleted successfully');
}

    public function getAllCompanies()
    {
        $companies = Company::select('id', 'name')->get();
        return response()->json($companies);
    }

}