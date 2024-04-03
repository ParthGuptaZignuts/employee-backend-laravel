<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;


use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::all();
        return response()->json($companies, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:64',
            'company_email' => 'required|email|max:128',
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

        $company = new Company();
        $company->name = $request->name;
        $company->company_email = $request->company_email;
        $company->website = $request->website;
        $company->address = $request->address;
        $company->status = $request->status;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('public/logos');
            $company->logo_url = basename($logoPath);
        }

        $company->save();


        $admin = new User();
        $admin->first_name = $request->input('admin.first_name');
        $admin->last_name = $request->input('admin.last_name');
        $admin->type = "CA";
        $admin->email = $request->input('admin.email');
        $admin->address = $request->input('admin.address');
        $admin->city = $request->input('admin.city');
        $admin->dob = $request->input('admin.dob');
        $admin->password = Hash::make($admin->password);
        $admin->save();
        $company->admin()->associate($admin);
        $company->save();


        $companyUser = new CompanyUser();
        $companyUser->company_id = $company->id;
        $companyUser->user_id = $admin->id;
        $companyUser->save();
    

        return response()->json($company, 201);
    }

    public function update(Request $request, string $id)
    {
        $validator = $this->validate($request, [
            'name' => 'required|string|max:64',
            'company_email' => 'required|email|max:128' . $id,
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
        $company->save();

        if ($request->has('admin')) {
            $adminData = $validator['admin'];
            $admin = $company->admin;
            if ($admin) {
                $admin->update($adminData);
            } else {
                return response()->json(['error' => 'Admin user not found'], 404);
            }
        }
        if ($request->has('company_user')) {
            $companyUserData = $validator['company_user'];
            $companyUser = $company->companyUser()->first(); 
            if ($companyUser) {
                $companyUser->update($companyUserData);
            } else {
                return response()->json(['error' => 'Company user not found'], 404);
            }
        }
    
        return response()->json(['message' => 'Company updated successfully', 'company' => $company], 200);
    }



    public function show($companyId)
    {
        $company = Company::with('admin')->find($companyId);

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        return response()->json($company, 200);
    }

    public function destroy(string $id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Company deleted successfully'], 200);
    }
}
