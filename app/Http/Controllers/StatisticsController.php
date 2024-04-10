<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use App\Models\JobDescription;

class StatisticsController extends Controller
{
    public function getStatistics()
    {
        $totalCompanies = Company::count();
        $totalCompanyAdmin = User::whereIn('type', ['CA'])->count();
        $totalEmployees = User::whereIn('type', ['E'])->count();
        $totalJobs = JobDescription::count();

        return response()->json([
            'total_companies' => $totalCompanies,
            'total_employees' => $totalEmployees,
            'total_ca' => $totalCompanyAdmin,
            'total_jobs' => $totalJobs,
        ]);
    }
}
