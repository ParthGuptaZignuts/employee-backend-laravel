<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Preference;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationMail;


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
            $employees = User::whereIn('type', ['CA', 'E'])->get();
            return response()->json($employees);
        } elseif (auth()->user()->type === 'CA') {
            $employees = User::where('company_id', auth()->user()->company_id)
                ->whereIn('type', ['CA', 'E'])
                ->get();
            return response()->json($employees);
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
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
            return response()->json(['error' => $validator->errors()], 422);
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

            return response()->json(['message' => 'User created successfully'], 201);
        } else {
            return response()->json(['error' => 'Only users with type SA can create employees'], 403);
        }
    }

    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
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
                return response()->json(['error' => $validator->errors()], 422);
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

            return response()->json(['message' => 'User updated successfully'], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (auth()->user()->type === 'SA' || (auth()->user()->type === 'CA' && auth()->user()->company_id === $user->company_id)) {
            return response()->json(['data' => $user], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (auth()->user()->type === 'SA' || (auth()->user()->type === 'CA' && auth()->user()->company_id === $user->company_id)) {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully'], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }
}
