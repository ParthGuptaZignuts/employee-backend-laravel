<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Annoucement;
use Carbon\Carbon;

class AnnoucementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // validation missing for status, per_page, page_number
            $query = Annoucement::query();
            $status = $request->input('status');

            if($status == 'not_seen'){
                $query->where('status', $status);
            }
            elseif($status == 'seen'){
                $query->where('status', $status);
            }
            
            if(isset($request->per_page) && isset($request->page_number))
            {
                // set pagination here and push into $data
            }

            $annoucenment = $query->paginate(10); // wrong

            return ok('Annoucement retrieved successfully', $annoucenment);

        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $request->validate([
                'message'  => 'required | string',
                'date'     => 'required | date',
                'time'     => 'required',
                'status'   => 'string',
            ]);

            $annoucenment = Annoucement::create($request->only('message', 'date', 'time', 'status'));

            return ok('Company updated successfully', $annoucenment, 200);

        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
            $annoucenment = Annoucement::findOrFail($id);
            return ok('Annoucement retrieved successfully', $annoucenment);

    }

    public function update(Request $request, string $id)
    {
        try {
            
            $request->validate([
                'message' => 'sometimes|string',
                'date'    => 'sometimes|date',
                'time'    => 'sometimes',
                'status'  => 'sometimes|string',
            ]);
            
            $announcement = Annoucement::findOrFail($id);
            $currentDateTime = Carbon::now();
            $requestedDateTime = Carbon::parse("{$request->date} {$request->time}");

            if ($requestedDateTime->gt($currentDateTime)) {
                $announcement->fill($request->only('message', 'status', 'date', 'time'));
                $announcement->save();

                return response()->json([
                    'message' => 'Announcement updated successfully',
                    'announcement' => $announcement,
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Date and time must be in the future',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Find the announcement by ID or fail with a 404 response if not found
            $announcement = Annoucement::findOrFail($id);
            $currentDateTime = Carbon::now();
            $announcementDateTime = Carbon::parse("{$announcement->date} {$announcement->time}");
            if ($announcementDateTime->gt($currentDateTime)) {
                $announcement->delete();
                return response()->json([
                    'message' => 'Announcement deleted successfully',
                    'announcement' => $announcement
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Cannot delete past announcements'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
