<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserSeeder extends Seeder
{
    public function run(): void
    {
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
            'employee_number' => "1",
        ]);

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
            'employee_number' => "2",
        ]);

    }
}
