<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Preference;

class UserSeeder extends Seeder
{   
    public function run(): void
    {
        $employeeNumber = $this->generateEmployeeNumber();
        // Company Admin
        User::create([
            'first_name' => 'Company1',
            'last_name' => 'Admin',
            'email' => 'admin1@example.com',
            'address' => '123 First Street, City',
            'dob' => '1990-01-01',
            'city' => 'CityName',
            'password' => Hash::make('password'),
            'type' => 'CA',
            'company_id' => 1,
            'joining_date' => now(),
            'employee_number' => $employeeNumber,
        ]);
        $nextEmployeeNumber = $this->generateEmployeeNumber();
        // Employee
        User::create([
            'first_name' => 'Employee1',
            'last_name' => 'Admin',
            'email' => 'employee1@example.com',
            'address' => '456 Second Street, City',
            'dob' => '1991-02-02',
            'city' => 'CityName',
            'password' => Hash::make('password'),
            'type' => 'E',
            'company_id' => 1,
            'joining_date' => now(),
            'employee_number' => $nextEmployeeNumber,
        ]);

    }

    public function generateEmployeeNumber(): string
    {
        try {
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
        } catch (\Exception $e) {
            throw new \Exception("An unexpected error occurred while generating employee number.");
        }
    }

}
