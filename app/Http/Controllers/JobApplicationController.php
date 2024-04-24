<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\JobDescription;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
{
    //
    public function store(Request $request)
    {
        // Validate input and ensure the resume is provided and is a file
        $validatedData = $request->validate([
            'email' => 'required |email',
            'job_descriptions_id' => 'required|integer',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $user = User::where('email', $validatedData['email'])->firstOrFail();
        $jobDescription = JobDescription::findOrFail($validatedData['job_descriptions_id']);
        $companyId = $jobDescription->company_id;

        $existingApplication = JobApplication::where('user_id', $user->id)
            ->where('job_descriptions_id', $validatedData['job_descriptions_id'])
            ->first();

        if ($existingApplication) {
            // Return response indicating that the user has already applied for this job
            return response()->json(['message' => 'You have already applied for this post and your request is pending.'], 400);
        }

        // Store the uploaded file and get the file path
        $resumePath = $request->file('resume')->store('resumes', 'public'); // Save to storage/app/public/resumes

        // Create a new job application with the resume path
        $application = JobApplication::create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'job_descriptions_id' => $validatedData['job_descriptions_id'],
            'resume' => $resumePath,
            'status' => $validatedData['status'] ?? 'P',
        ]);

        return response()->json($application, 201);
    }

    //     public function getAllDetails()
    //     {
    //         $user = Auth::user();
    //         if ($user->type === 'SA') {
    //             $applications = JobApplication::with(['user', 'company', 'jobDescription'])->get();
    //             $result = $applications->map(function ($application) {
    //                 return [
    //                     'application_id' => $application->id,
    //                     'candidate_name' => $application->user->first_name,
    //                     'company_name' => $application->company->name,
    //                     'job_title' => $application->jobDescription->title,
    //                     'resume_path' => $application->resume,
    //                     'status' => $application->status,

    //                 ];
    //             });
    //         }
    //         elseif ($user->type === 'CA') {

    //         }

    //         return response()->json($result, 200);
    //     }
    public function getAllDetails()
    {
        $user = Auth::user();

        if ($user->type === 'SA') {
            // If the user is a Super Admin, they should see all applications
            $applications = JobApplication::with(['user', 'company', 'jobDescription'])->get();
        } elseif ($user->type === 'CA') {
            // If the user is a Company Admin, only show applications from their company
            $applications = JobApplication::with(['user', 'company', 'jobDescription'])
                ->where('company_id', $user->company_id) // Filter by the company of the current user
                ->get();
        }

        // Format the response
        $result = $applications->map(function ($application) {
            return [
                'application_id' => $application->id,
                'candidate_name' => $application->user->first_name,
                'company_name' => $application->company->name,
                'job_title' => $application->jobDescription->title,
                'resume_path' => $application->resume,
                'status' => $application->status,
            ];
        });

        return response()->json($result, 200);
    }
}
