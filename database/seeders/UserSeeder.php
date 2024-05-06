<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Preference;
use Faker\Factory as Faker;
use App\Http\Helpers\GenerateEmployeeNumber;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Create 5 company admins with unique IDs
        for ($i = 1; $i <= 5; $i++) {

            User::create([
                'first_name'      => ($i == 1) ? 'Company' : $faker->firstName(),
                'last_name'       => ($i == 1) ? 'Admin' : $faker->lastName(),
                'email'           => ($i == 1) ? 'company@admin.com' : 'admin' . $i . '@example.com',
                'address'         => $faker->address(),
                'dob'             => $faker->date('Y-m-d', '1990-01-01'),
                'city'            => $faker->city(),
                'password'        => Hash::make('password'), // Password for all users
                'type'            => 'CA',
                'company_id'      => $i, // 1 to 5 for company admins
                'joining_date'    => now(),
                'employee_number' => GenerateEmployeeNumber::generateEmployeeNumber(),
            ]);
        }

        // Create 10 employees with random company IDs between 1 and 5
        for ($i = 1; $i <= 10; $i++) {

            User::create([
                'first_name'      => $faker->firstName(),
                'last_name'       => $faker->lastName(),
                'email'           => 'employee' . $i . '@example.com',
                'address'         => $faker->address(),
                'dob'             => $faker->date('Y-m-d', '1991-01-01'),
                'city'            => $faker->city(),
                'password'        => Hash::make('password'), 
                'type'            => 'E',
                'company_id'      => $faker->numberBetween(1, 5), // Random company ID between 1 and 5
                'joining_date'    => now(),
                'employee_number' => GenerateEmployeeNumber::generateEmployeeNumber(),
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {


            User::create([
                'first_name' => 'user' . $i,
                'last_name'  => 'user' . $i,
                'email'      => 'user' . $i . '@example.com',
                'phone'      => '1234567890',
                'dob'        => $faker->date('Y-m-d', '1991-01-01'),
                'city'       => $faker->city(),
                'password'   => Hash::make('password'),
                'address'    => $faker->address(),
                'type'       => 'C',
            ]);
        }
    }
}
