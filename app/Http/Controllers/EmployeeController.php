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

require_once app_path('Http/Helpers/APIResponse.php');

class EmployeeController extends Controller
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
        if (auth()->user()->type === 'SA') {
            $employees = User::with('company:id,name')->whereIn('type', ['CA', 'E'])->get();
            return ok('Employees retrieved successfully', $employees);
        } elseif (auth()->user()->type === 'CA') {
            $employees = User::with('company:id,name')
                ->where('company_id', auth()->user()->company_id)
                ->whereIn('type', ['CA', 'E'])
                ->get();
            return ok('Employees retrieved successfully', $employees);
        } else {
            return error('Unauthorized', [], 'forbidden');
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'dob' => 'required|date',
            'joining_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return error('Validation Failed. Please check the request attributes and try again.', $validator->errors()->toArray(), 'validation');
        }

        $company = Company::find($request->company_id);
        if (!$company) {
            return error('Company not found', [], 'notfound');
        }

        if (auth()->user()->type === 'SA') {
            $user = new User();
            $user->type = 'E';
            $user->company_id = $request->company_id;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->address = $request->address;
            $user->city = $request->city;
            $user->dob = $request->dob;
            $user->joining_date = $request->joining_date;
            $user->employee_number = $this->generateEmployeeNumber();
            $user->save();

            Mail::to($user['email'])->send(new EmployeeInvitaion(
                $user['first_name'],
                $user['last_name'],
                $user['email'],
                $user['employee_number'],
                $company['name'],
                $company['website']
            ));

            return ok('User created successfully');
        } else {
            return error('Only users with type SA can create employees', [], 'forbidden');
        }
    }

    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return error('User not found', [], 'notfound');
        }

        if (auth()->user()->type === 'SA' || (auth()->user()->type === 'CA' && auth()->user()->company_id === $user->company_id)) {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email|unique:users,email,' . $id,
                'password' => 'sometimes|required|string',
                'address' => 'required|string',
                'city' => 'required|string',
                'dob' => 'required|date',
                'joining_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return error('Validation Failed. Please check the request attributes and try again.', $validator->errors()->toArray(), 'validation');
            }

            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => $request->has('password') ? Hash::make($request->password) : $user->password,
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

    public function show(string $id)
    {
        $user = User::with('company:id,name')->find($id);

        if (!$user) {
            return error('User not found', [], 'notfound');
        }

        if (auth()->user()->type === 'SA' || (auth()->user()->type === 'CA' && auth()->user()->company_id === $user->company_id)) {
            return ok('User retrieved successfully', $user);
        } else {
            return error('Unauthorized', [], 'forbidden');
        }
    }

    public function destroy(string $id, Request $request)
    {
        $user = User::find($id);

        if (!$user) {
            return error('User not found', [], 'notfound');
        }

        if (auth()->user()->type === 'SA' || (auth()->user()->type === 'CA' && auth()->user()->company_id === $user->company_id)) {
            if ($request->has('permanent_delete')) {
                $user->forceDelete();
                return ok('User permanently deleted successfully');
            } else {
                $user->delete();
                return ok('User soft deleted successfully');
            }
        } else {
            return error('Unauthorized', [], 'forbidden');
        }
    }
}
