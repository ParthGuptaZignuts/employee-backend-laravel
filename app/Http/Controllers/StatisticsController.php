<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use App\Models\JobDescription;

class StatisticsController extends Controller
{
    public function getStatistics(Request $request)
    {
        if ($request->user()->type === 'SA') {
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
        } elseif ($request->user()->type === 'CA') { 
            $companyId = $request->user()->company_id;
            $totalEmployees = User::where('type', 'E')->where('company_id', $companyId)->count();
            $totalJobs = JobDescription::whereHas('company', function ($query) use ($companyId) {
                $query->where('id', $companyId);
            })->count();

            return response()->json([
                'total_employees' => $totalEmployees,
                'total_jobs' => $totalJobs,
            ]);
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }
}
