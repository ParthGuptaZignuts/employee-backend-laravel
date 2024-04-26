<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            'name' => 'Test Company',
            'email' => 'TestCompany@example.com',
            'website' => 'http://TestCompany.com',
            'logo' => asset('storage/logos/logoOne.png'),
            'address' => 'India',
            'status' => 'A',
        ]);

        Company::create([
            'name' => 'Test Company 1',
            'email' => 'TestCompany1@example.com',
            'website' => 'http://TestCompany1.com',
            'logo' => asset('storage/logos/logoOne.png'),
            'address' => 'India',
            'status' => 'A',
        ]);

        // Company 2
        Company::create([
            'name' => 'Test Company 2',
            'email' => 'TestCompany2@example.com',
            'website' => 'http://TestCompany2.com',
            'logo' => asset('storage/logos/logoTwo.png'),
            'address' => 'USA',
            'status' => 'A',
        ]);

        // Company 3
        Company::create([
            'name' => 'Test Company 3',
            'email' => 'TestCompany3@example.com',
            'website' => 'http://TestCompany3.com',
            'logo' => asset('storage/logos/logoThree.png'),
            'address' => 'UK',
            'status' => 'A',
        ]);
    }
}
