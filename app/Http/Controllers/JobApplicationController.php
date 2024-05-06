<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\JobDescription;
use Illuminate\Support\Facades\Auth;

require_once app_path('Http/Helpers/APIResponse.php');

class JobApplicationController extends Controller
{
    /**
     * storing the job application information of the user
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,'checkUserType:SA,CA'
     * @route /userJobDetails
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validate input and ensure the resume is provided and is a file
            $validatedData = $request->validate([
                'email'               => 'required|email',
                'job_descriptions_id' => 'required|integer',
                'resume'              => 'required|file|mimes:pdf,doc,docx|max:10240',
            ]);
            // finding the user
            $user = User::where('email', $validatedData['email'])->firstOrFail();

            // finding the jobs
            $jobDescription = JobDescription::findOrFail($validatedData['job_descriptions_id']);
            $companyId = $jobDescription->company_id;

            // checking if the user already exists
            $existingApplication = JobApplication::where('user_id', $user->id)
                ->where('job_descriptions_id', $validatedData['job_descriptions_id'])
                ->first();

            // returning if exists
            if ($existingApplication) {
                return error('You have already applied for this post and your request is pending.', [], 'duplicate', 400);
            }

            // Store the uploaded file and get the file path
            $resumePath = $request->file('resume')->store('resumes', 'public'); // Save to storage/app/public/resumes

            // Create a new job application with the resume path
            $application = JobApplication::create([
                'user_id'                   => $user->id,
                'company_id'                => $companyId,
                'job_descriptions_id'       => $validatedData['job_descriptions_id'],
                'resume'                    => $resumePath,
                'status'                    => $validatedData['status'] ?? 'P',
            ]);

            return ok('Job application created successfully.', $application, 201);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * getting all the detials of job applications
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA ,CA'(superAdmin , companyAdmin)
     * @route /allCandidateInfo
     * @return \Illuminate\Http\Response
     */
    public function getAllDetails()
    {
        try {
            $user = Auth::user();

            if ($user->type === 'SA') {
                $applications = JobApplication::with(['user', 'company', 'jobDescription'])->get();
            } elseif ($user->type === 'CA') {
                $applications = JobApplication::with(['user', 'company', 'jobDescription'])
                    ->where('company_id', $user->company_id)
                    ->get();
            } else {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            $result = $applications->map(function ($application) {
                return [
                    'application_id'        => $application->id,
                    'candidate_name'        => $application->user->first_name . ' ' . $application->user->last_name,
                    'company_name'          => $application->company ? $application->company->name : 'Unknown', 
                    'job_title'             => $application->jobDescription ? $application->jobDescription->title : 'Unknown',
                    'resume_path'           => $application->resume,
                    'status'                => $application->status,
                ];
            });

            return response()->json(['message' => 'Job applications retrieved successfully.', 'data' => $result], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     *getting the particular job application
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA ,CA'(superAdmin , companyAdmin)
     * @route /allCandidateInfo/{id}
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            // Find the job application with its relations
            $application = JobApplication::with(['user', 'company', 'jobDescription'])->findOrFail($id);

            // checking the user type
            if ($user->type === 'CA' && $application->company_id !== $user->company_id) {
                return error('You do not have permission to view this application.', [], 'unauthorized', 403);
            } elseif ($user->type !== 'SA' && $application->user_id !== $user->id) {
                return error('You do not have permission to view this application.', [], 'unauthorized', 403);
            }

            // creating the needed result
            $result = [
                'application_id'        => $application->id,
                'candidate_name'        => $application->user->first_name . ' ' . $application->user->last_name,
                'company_name'          => $application->company->name,
                'job_title'             => $application->jobDescription->title,
                'resume_path'           => $application->resume,
                'status'                => $application->status,
            ];

            return ok('Job application retrieved successfully.', $result);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * updating the particular job application
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA ,CA'(superAdmin , companyAdmin)
     * @route /allCandidateInfo/delete/{id}
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|in:P,A,R',
            ]);

            $user = Auth::user();

            // Find the job application
            $application = JobApplication::findOrFail($id);

            if ($user->type === 'CA' && $application->company_id !== $user->company_id) {
                return error('You do not have permission to update this application.', [], 'unauthorized', 403);
            } elseif ($user->type !== 'SA' && $application->user_id !== $user->id) {
                return error('Unauthorized update request.', [], 'unauthorized', 403);
            }

            $application->update([
                'status' => $validatedData['status'],
            ]);

            return ok('Job application updated successfully.', $application);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * deleting the particular job application
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,checkUserType:SA ,CA'(superAdmin , companyAdmin)
     * @route /allCandidatesInfo/delete/{id}
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $id)
    {
        try {
            $user = Auth::user();

            // Find the job application
            $application = JobApplication::findOrFail($id);

            // checking the user type
            if ($user->type === 'SA') {
                if ($request->query('hard') === 'true' && $request->boolean('hard')) {
                    $application->forceDelete(); // Hard delete
                    return ok('Job application permanently deleted.', [], 200);
                } else {
                    $application->delete(); // Soft delete
                    return ok('Job application soft deleted.', [], 200);
                }
            } elseif ($user->type === 'CA') {
                if ($application->company_id !== $user->company_id) {
                    return error('You do not have permission to delete this application.', [], 'unauthorized', 403);
                }

                if ($request->query('hard') === 'true' && $request->boolean('hard')) {
                    $application->forceDelete();
                    return ok('Job application permanently deleted.', [], 200);
                } else {
                    $application->delete();
                    return ok('Job application soft deleted.', [], 200);
                }
            }

            return error('You do not have permission to delete this application.', [], 'unauthorized', 403);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * getting the job status 
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @route /jobsStatus
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function jobsStatus(Request $request)
    {
        try {
            // validate user id
            $validatedData = $request->validate([
                'user_id' => 'required|integer',
            ]);

            // finding the application
            $applications = JobApplication::with(['jobDescription', 'company'])
                ->where('user_id', $validatedData['user_id'])
                ->get();

            if ($applications->isEmpty()) {
                return error('No job applications found for this user.', [], 'notfound', 404);
            }

            // generating the needful result
            $result = $applications->map(function ($application) {
                return [
                    'application_id'    => $application->id,
                    'job_title'         => $application->jobDescription->title,
                    'company_name'      => $application->company->name,
                    'company_location'  => $application->company->address,
                    'job_expiry'        => $application->jobDescription->expiry_date,
                    'job_salary'        => $application->jobDescription->salary,
                    'status'            => $application->status,
                    'resume'            => $application->resume,
                ];
            });

            return ok('Job applications for the user retrieved successfully.', $result);
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }
}
