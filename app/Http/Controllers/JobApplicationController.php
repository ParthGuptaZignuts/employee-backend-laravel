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

    public function show($id)
    {
        $user = Auth::user(); // Get the authenticated user

        // Find the job application with its relations
        $application = JobApplication::with(['user', 'company', 'jobDescription'])->findOrFail($id);

        // Determine if the authenticated user has permission to view this application
        if ($user->type === 'SA') {
            // Super Admin can view all applications
        } elseif ($user->type === 'CA') {
            // Company Admin can only view applications from their company
            if ($application->company_id !== $user->company_id) {
                return response()->json(['message' => 'You do not have permission to view this application.'], 403);
            }
        } else {
            // Regular users can only view their own applications
            if ($application->user_id !== $user->id) {
                return response()->json(['message' => 'You do not have permission to view this application.'], 403);
            }
        }

        // Return the application details in a formatted response
        $result = [
            'application_id' => $application->id,
            'candidate_name' => $application->user->first_name . ' ' . $application->user->last_name,
            'company_name' => $application->company->name,
            'job_title' => $application->jobDescription->title,
            'resume_path' => $application->resume,
            'status' => $application->status,
        ];

        return response()->json($result, 200); // Success response
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status' => 'required|in:P,A,R',
        ]);

        $user = Auth::user(); // Get the authenticated user

        // Find the job application
        $application = JobApplication::findOrFail($id); // Find or return a 404 error if not found

        // Super Admin can update any application
        if ($user->type === 'SA') {
            // Update the application details
            $application->update([
                'status' => $validatedData['status'],
            ]);

            return response()->json(['message' => 'Job application updated successfully.'], 200);
        }

        // Company Admin can only update applications within their company
        if ($user->type === 'CA') {
            if ($application->company_id !== $user->company_id) {
                return response()->json(['message' => 'You do not have permission to update this application.'], 403); // Forbidden response
            }

            // Update the application details if it belongs to the same company
            $application->update([
                'status' => $validatedData['status'],
            ]);

            return response()->json(['message' => 'Job application updated successfully.'], 200);
        }

        return response()->json(['message' => 'You do not have permission to update this application.'], 403); // Default forbidden response
    }


    public function delete(Request $request, $id)
    {
        $user = Auth::user(); // Get the authenticated user

        // Find the job application
        $application = JobApplication::findOrFail($id);

        if ($user->type === 'SA') {
            // Super Admin can choose soft or hard delete
            if ($request->query('hard') === 'true') {
                $application->forceDelete(); // Hard delete
                return response()->json(['message' => 'Job application permanently deleted.'], 200);
            } else {
                $application->delete(); // Soft delete
                return response()->json(['message' => 'Job application soft deleted.'], 200);
            }
        }

        if ($user->type === 'CA') {
            // Company Admin can only delete applications from their own company
            if ($application->company_id !== $user->company_id) {
                return response()->json(['message' => 'You do not have permission to delete this application.'], 403); // Forbidden response
            }

            // Company Admin can choose soft or hard delete
            if ($request->query('hard') === 'true') {
                $application->forceDelete(); // Hard delete
                return response()->json(['message' => 'Job application permanently deleted.'], 200);
            } else {
                $application->delete(); // Soft delete
                return response()->json(['message' => 'Job application soft deleted.'], 200);
            }
        }

        // Default forbidden response
        return response()->json(['message' => 'You do not have permission to delete this application.'], 403);
    }
}
