<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobDescription;

class JobDescriptionController extends Controller
{

    public function index()
    {
        $jobDescriptionDescription = JobDescription::all();
        return response()->json($jobDescriptionDescription); 
    }

    public function store(Request $request)
    {
        $validator=$this->validate($request, [
            'company_id' => $request->user()->type === 'SA' ? 'required|exists:companies,id' : 'nullable|exists:companies,id',
            'title' => 'required|string|max:255',
            'salary' => 'nullable|numeric',
            'employment_type' => 'nullable|string',
            'required_experience' => 'nullable|string',
            'required_skills' => 'nullable|string',
            'posted_date' => 'nullable|date', 
            'expiry_date' => 'nullable|date',
        ]);
        
       
        
        $validator['company_id'] = $request->user()->type==="SA"? $request->get('company_id') : $request->user()->company_id; 
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
        $validator=$this->validate($request, [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'salary' => 'sometimes|nullable|numeric',
            'employment_type' => 'sometimes|nullable|string',
            'required_experience' => 'sometimes|nullable|string',
            'required_skills' => 'sometimes|nullable|string',
            'expiry_date' => 'sometimes|nullable|date',
        ]);
    
        
    
        $jobDescription = JobDescription::find($id);
    
        if (!$jobDescription) {
            return response()->json(['error' => 'Job not found'], 404);
        }
    
        $jobDescription->update($validator);
    
        return response()->json(['message' => 'Job updated successfully', 'job' => $jobDescription], 200);
    }

 
    public function destroy(string $id)
    {
        $jobDescription = JobDescription::find($id);

        if (!$jobDescription) {
            return response()->json(['error' => 'Job not found'], 404);
        }
       
        $jobDescription->delete();
    
        return response()->json(['message' => 'Job deleted successfully'], 200);
    }
}
