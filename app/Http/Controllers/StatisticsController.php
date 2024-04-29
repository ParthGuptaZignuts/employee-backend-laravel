<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use App\Models\JobDescription;
use App\Models\JobApplication;
require_once app_path('Http/Helpers/APIResponse.php');

class StatisticsController extends Controller
{
    /**
     * Method to get all the statistics of total companies total jobs , total employees total company admin and total jobs applications
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,
     * @route /statistics
     * @return \Illuminate\Http\Response
     */
    public function getStatistics(Request $request)
    {
        try {
            $userType = $request->user()->type;

            if ($userType === 'SA') {
                // Super Admin statistics
                $totalCompanies = Company::count();
                $totalCompanyAdmin = User::where('type', 'CA')->count();
                $totalEmployees = User::where('type', 'E')->count();
                $totalJobs = JobDescription::count();
                $totalApplications = JobApplication::count();

                return ok('Statistics retrieved successfully', [
                    'total_companies' => $totalCompanies,
                    'total_employees' => $totalEmployees,
                    'total_ca' => $totalCompanyAdmin,
                    'total_jobs' => $totalJobs,
                    'total_Application' => $totalApplications,
                ]);
            } elseif ($userType === 'CA') {
                // Company Admin statistics
                $companyId = $request->user()->company_id;

                $totalEmployees = User::where('type', 'E')->where('company_id', $companyId)->count();
                $totalJobs = JobDescription::where('company_id', $companyId)->count();
                $totalApplications = JobApplication::where('company_id', $companyId)->count();

                return ok('Company-specific statistics retrieved successfully', [
                    'total_employees' => $totalEmployees,
                    'total_jobs' => $totalJobs,
                    'total_Application' => $totalApplications,
                ]);
            } else {
                // Unauthorized for other user types
                return error('Unauthorized', [], 'unauthorized');
            }
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], 'unexpected_error');
        }
    }
}
