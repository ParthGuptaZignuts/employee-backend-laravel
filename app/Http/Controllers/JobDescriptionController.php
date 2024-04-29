<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobDescription;

require_once app_path('Http/Helpers/APIResponse.php');

class JobDescriptionController extends Controller
{
    /**
     * Method to retrieve job descriptions based on user's role and filters
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,'checkUserType:SA,CA'
     * @route /jobs
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $jobDescriptions = JobDescription::query();

        // if user is Super Admin
        if ($request->user()->type === 'SA') {
            // checking if request has search and more than 3 letter to search 
            if ($request->has('search') && strlen($request->input('search')) >= 3) {
                $searchQuery = $request->input('search');
                $jobDescriptions = $jobDescriptions->where('title', 'like', "%$searchQuery%");
            }

            // filter on bases of employement type for super admin
            if ($request->has('employment_type')) {
                $employmentType = $request->input('employment_type');
                $jobDescriptions = $jobDescriptions->where('employment_type', $employmentType);
            }

            $jobDescriptions = $jobDescriptions->with('company')->get();
        } else {
            // this is for Company Admin
            $jobDescriptions = $jobDescriptions->where('company_id', $request->user()->company_id);

            // checking if request has search and more than 3 letter to search 
            if ($request->has('search') && strlen($request->input('search')) >= 3) {
                $searchQuery = $request->input('search');
                $jobDescriptions = $jobDescriptions->where('title', 'like', "%$searchQuery%");
            }

            // filter on bases of employement type for company admin
            if ($request->has('employment_type')) {
                $employmentType = $request->input('employment_type');
                $jobDescriptions = $jobDescriptions->where('employment_type', $employmentType);
            }

            $jobDescriptions = $jobDescriptions->with('company')->get();
        }

        // return response()->json($jobDescriptions);
        return ok('Job descriptions retrieved successfully', $jobDescriptions);
    }

    /**
     * Method to store a new job description
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,'checkUserType:SA,CA'
     * @route /jobs/create
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validation rules
        $rules = [
            'title' => 'required|string',
            'salary' => 'nullable|numeric',
            'employment_type' => 'required|string',
            'experience_required' => 'nullable|string',
            'skills_required' => 'nullable|string',
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

        return ok('Job description created successfully', $jobDescription, 201);
    }
    /**
     * Method to retrieve details of a specific job description
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,'checkUserType:SA,CA'
     * @route /jobs/{id}
     * @return \Illuminate\Http\Response
     */

    public function show(string $id)
    {
        // get job by id
        $jobDescription = JobDescription::find($id);

        // return if job does not exist
        if (!$jobDescription) {
            return error('Job not found', [], 'notfound', 404);
        }

        return ok('Job description retrieved successfully', $jobDescription);
    }

    /**
     * Method to update an existing job description
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,'checkUserType:SA,CA'
     * @route /jobs/update/{id}
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, string $id)
    {
        // Validation rules
        $rules = [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'salary' => 'sometimes|nullable|numeric',
            'employment_type' => 'sometimes|nullable|string',
            'experience_required' => 'sometimes|nullable|string',
            'skills_required' => 'sometimes|nullable|string',
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
            return error('Job not found', [], 'notfound', 404);
        }

        // Check if the user has permission to update this job
        if ($request->user()->type !== 'SA' && $jobDescription->company_id !== $request->user()->company_id) {
            return error('Unauthorized', [], 'unauthorized', 403);
        }

        // Update job description
        $jobDescription->update($validator);

        return ok('Job updated successfully', $jobDescription, 200);
    }

    /**
     * Method to delete a job description
     * @method POST
     * @author Parth Gupta (Zignuts Technolab)
     * @authentication Requires authentication
     * @middleware auth:api,'checkUserType:SA,CA'
     * @route /jobs/delete/{id}
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, string $id)
    {
        // Find job description
        $jobDescription = JobDescription::find($id);

        if (!$jobDescription) {
            return error('Job not found', [], 'notfound', 404);
        }

        // Check if the user has permission to delete this job
        if ($request->user()->type !== 'SA' && $jobDescription->company_id !== $request->user()->company_id) {
            return error('Unauthorized', [], 'unauthorized', 403);
        }

        // Perform soft delete or permanent delete based on request parameter
        if ($request->has('permanent_delete') && $request->boolean('permanent_delete')) {
            $jobDescription->forceDelete(); // Permanent delete
            return ok('Job permanently deleted successfully', [], 200);
        } else {
            $jobDescription->delete(); // Soft delete
            return ok('Job deleted successfully', [], 200);
        }
    }
    /**
     * Method to get all the job descriptions
     * @method GET
     * @author Parth Gupta (Zignuts Technolab)
     * @route /jobsInfo
     * @return \Illuminate\Http\Response
     */
    public function AllJobsInfo()
    {
        $jobDescriptions = JobDescription::with(['company:id,name,logo,email,address'])->get();
        return ok('All job information retrieved successfully', $jobDescriptions);
    }
}
