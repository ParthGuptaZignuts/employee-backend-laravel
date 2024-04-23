<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\JobDescription;

class JobApplicationController extends Controller
{
    //
    public function store(Request $request)
    {
        // Validate input and ensure the resume is provided and is a file
        $validatedData = $request->validate([
            'email' =>'required |email',
            'job_descriptions_id' => 'required|integer',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $user = User::where('email', $validatedData['email'])->firstOrFail();
        $jobDescription = JobDescription::findOrFail($validatedData['job_descriptions_id']);
        $companyId = $jobDescription->company_id;
        
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
}
