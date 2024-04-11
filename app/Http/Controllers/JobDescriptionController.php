<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobDescription;

class JobDescriptionController extends Controller
{

    public function index(Request $request)
    {
        if ($request->user()->type === 'SA') {
            $jobDescriptions = JobDescription::all();
        } else {
            $jobDescriptions = JobDescription::where('company_id', $request->user()->company_id)->get();
        }

        return response()->json($jobDescriptions);
    }

    public function store(Request $request)
    {
        // Validation rules
        $rules = [
            'title' => 'required|string',
            'salary' => 'nullable|numeric',
            'employment_type' => 'nullable|string',
            'experience_required' => 'nullable|string',
            'required_skills' => 'nullable|string',
            'posted_date' => 'nullable|date', 
            'expiry_date' => 'nullable|date',
        ];

        // If the user is a super admin, add validation rule for company_id
        if ($request->user()->type === 'SA') {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        // Validate the request
        $validator = $this->validate($request, $rules);

        // Determine company_id based on user type
        $validator['company_id'] = $request->user()->type === "SA" ? $request->get('company_id') : $request->user()->company_id;

        // Create job description
        $jobDescription = JobDescription::create($validator);

        return response()->json($jobDescription, 201);
    }



    public function show(string $id)
    {
        $jobDescription = JobDescription::find($id);
       
        if (!$jobDescription) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($jobDescription);
    }


    public function update(Request $request, string $id)
    {
        // Validation rules
        $rules = [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'salary' => 'sometimes|nullable|numeric',
            'employment_type' => 'sometimes|nullable|string',
            'experience_required' => 'sometimes|nullable|string',
            'required_skills' => 'sometimes|nullable|string',
            'expiry_date' => 'sometimes|nullable|date',
        ];

        // If the user is a company admin, add validation rule for company_id
        if ($request->user()->type !== 'SA') {
            $rules['company_id'] = 'exists:companies,id';
        }

        // Validate the request
        $validator = $this->validate($request, $rules);

        // Find job description
        $jobDescription = JobDescription::find($id);

        if (!$jobDescription) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        // Check if the user has permission to update this job
        if ($request->user()->type !== 'SA' && $jobDescription->company_id !== $request->user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update job description
        $jobDescription->update($validator);

        return response()->json(['message' => 'Job updated successfully', 'job' => $jobDescription], 200);
    }

 
    public function destroy(Request $request, string $id)
    {
        // Find job description
        $jobDescription = JobDescription::find($id);

        if (!$jobDescription) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        // Check if the user has permission to delete this job
        if ($request->user()->type !== 'SA' && $jobDescription->company_id !== $request->user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Perform soft delete or permanent delete based on request parameter
        if ($request->has('permanent_delete') && $request->permanent_delete) {
            $jobDescription->forceDelete(); // Permanent delete
            return response()->json(['message' => 'Job permanently deleted successfully'], 200);
        } else {
            $jobDescription->delete(); // Soft delete
            return response()->json(['message' => 'Job deleted successfully'], 200);
        }
    }

}
