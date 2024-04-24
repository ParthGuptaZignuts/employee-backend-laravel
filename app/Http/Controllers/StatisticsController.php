<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use App\Models\JobDescription;
use App\Models\JobApplication;

class StatisticsController extends Controller
{
    public function getStatistics(Request $request)
    {
        // If the user is a super admin
        if ($request->user()->type === 'SA') {
            // Retrieve total counts of companies, company admins, employees, and job descriptions
            $totalCompanies = Company::count();
            $totalCompanyAdmin = User::whereIn('type', ['CA'])->count();
            $totalEmployees = User::whereIn('type', ['E'])->count();
            $totalJobs = JobDescription::count();
            $totalApplication = JobApplication::count();

            // Return the statistics as JSON response
            return response()->json([
                'total_companies' => $totalCompanies,
                'total_employees' => $totalEmployees,
                'total_ca' => $totalCompanyAdmin,
                'total_jobs' => $totalJobs,
                'total_Application' => $totalApplication
            ]);
        }
        // If the user is a company admin
        elseif ($request->user()->type === 'CA') {
            // Retrieve company ID of the logged-in company admin
            $companyId = $request->user()->company_id;

            // Retrieve total counts of employees and job descriptions for the company
            $totalEmployees = User::where('type', 'E')->where('company_id', $companyId)->count();
            $totalJobs = JobDescription::whereHas('company', function ($query) use ($companyId) {
                $query->where('id', $companyId);
            })->count();
            $totalApplications = JobApplication::where('company_id', $companyId)->count(); 

            // Return the statistics for the company as JSON response
            return response()->json([
                'total_employees' => $totalEmployees,
                'total_jobs' => $totalJobs,
                'total_Application' => $totalApplications
            ]);
        }
        // If the user is neither a super admin nor a company admin
        else {
            // Return unauthorized error
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }
}
