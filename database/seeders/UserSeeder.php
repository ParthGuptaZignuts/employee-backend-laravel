<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Preference;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Create 5 company admins with unique IDs
        for ($i = 1; $i <= 5; $i++) {
            $employeeNumber = $this->generateEmployeeNumber();

            User::create([
                'first_name' => ($i == 1) ? 'Company' : $faker->firstName(),
                'last_name' => ($i == 1) ? 'Admin' : $faker->lastName(),
                'email' => ($i == 1) ? 'company@admin.com' : 'admin' . $i . '@example.com',
                'address' => $faker->address(),
                'dob' => $faker->date('Y-m-d', '1990-01-01'),
                'city' => $faker->city(),
                'password' => Hash::make('password'), // Password for all users
                'type' => 'CA',
                'company_id' => $i, // 1 to 5 for company admins
                'joining_date' => now(),
                'employee_number' => $employeeNumber,
            ]);
        }

        // Create 10 employees with random company IDs between 1 and 5
        for ($i = 1; $i <= 10; $i++) {
            $employeeNumber = $this->generateEmployeeNumber();

            User::create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => 'employee' . $i . '@example.com',
                'address' => $faker->address(),
                'dob' => $faker->date('Y-m-d', '1991-01-01'),
                'city' => $faker->city(),
                'password' => Hash::make('password'), // Password for all users
                'type' => 'E',
                'company_id' => $faker->numberBetween(1, 5), // Random company ID between 1 and 5
                'joining_date' => now(),
                'employee_number' => $employeeNumber,
            ]);
        }
    }

    public function generateEmployeeNumber(): string
    {
        $latestEmployeeNumberPref = Preference::where('code', 'EMP')->first();

        if ($latestEmployeeNumberPref) {
            $latestEmployeeNumber = (int) $latestEmployeeNumberPref->value;
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
}
