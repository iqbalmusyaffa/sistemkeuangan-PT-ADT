<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unit;

class UnitsController extends Controller
{
    /**
     * Display a listing of the units.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Unit::select(['id', 'unit_name', 'unit_code']);
            return response()->json($data); // Return JSON response for AJAX request
        }
        return response()->json(Unit::all()); // Return all units as JSON if not an AJAX request
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'unit_name' => 'required|string|max:255|unique:units,unit_name',
            'unit_code' => 'required|string|max:255|unique:units,unit_code',
        ]);

        try {
            // Create a new unit record
            $unit = Unit::create($validatedData);

            // Return the created unit with a 201 status code
            return response()->json($unit, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create unit. Please try again later.'], 500);
        }
    }

    /**
     * Display the specified unit.
     */
    public function show($id)
    {
        $unit = Unit::findOrFail($id); // Find unit by ID or fail
        return response()->json($unit); // Return the unit as JSON
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(Request $request, $id)
    {
        // Find the unit by ID or fail
        $unit = Unit::findOrFail($id);

        // Validate incoming request data
        $validatedData = $request->validate([
            'unit_name' => 'required|string|max:255|unique:units,unit_name,' . $id,
            'unit_code' => 'required|string|max:255|unique:units,unit_code,' . $id,
        ]);

        // Update the unit record
        $unit->update($validatedData);

        // Return the updated unit
        return response()->json($unit);
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy($id)
    {
        $unit = Unit::findOrFail($id); // Find unit by ID or fail
        $unit->delete(); // Delete the unit

        // Return a 204 response (no content) to indicate successful deletion
        return response()->json(null, 204);
    }
}
