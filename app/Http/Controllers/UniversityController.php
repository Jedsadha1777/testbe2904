<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UniversityController extends Controller
{
    public function index()
    {
        //in case sql query use like this DB::select('SELECT * FROM university');
        $universities = DB::table('university')->get();
        if ($universities->isEmpty()) {
            return response()->json(['message' => 'University not found'], 404);
        }
        return response()->json($universities);
    }

    public function show($id)
    {        
        $university = DB::table('university')->where('university_id', $id)->first();

        if (!$university) {
            return response()->json(['message' => 'University not found'], 404);
        }

        $students = DB::table('university_student')
            ->join('student', 'university_student.student_id', '=', 'student.student_id')
            ->where('university_student.university_id', $id)
            ->select('student.*')
            ->get();

        if (!$students->isEmpty()) {
            $university->students = $students;
        }

        return response()->json($university);
    }

    public function store(Request $request)
    {
        try {           
            $request->validate([
                'name' => 'required|string|unique:university,name',
            ]);    
            $name = $request->input('name');
            DB::table('university')->insert(['name' => $name]);
            return response()->json(['message' => 'University inserted successfully'], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while inserting the university'], 500);

        }
    }

    public function update(Request $request, $id)
    {
        try {            
            $request->validate([
                'name' => 'required|string|unique:university,name,' . $id . ',university_id',
            ]);            
            $name = $request->input('name');
    
            $updated = DB::table('university')->where('university_id', $id)->update(['name' => $name]);
    
            if (!$updated) {
                return response()->json(['message' => 'University not found or no changes made'], 404);
            }
    
            Cache::forget('university_' . $id);          
            return response()->json(['message' => 'University updated successfully'], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
          
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
    
        } catch (\Exception $e) {
        
            \Log::error('An error occurred while updating the university', ['error' => $e->getMessage()]);
      
            return response()->json([
                'message' => 'An error occurred while updating the university',
                'error' => $e->getMessage()
            ], 500);
    
        }
    }
    

    public function destroy($id)
    {        
        $university = DB::table('university')->where('university_id', $id)->first();

        if (!$university) {
            return response()->json(['message' => 'University not found'], 404);
        }    

        DB::table('university')->where('university_id', $id)->delete();    
        Cache::forget("university_{$id}");
        Cache::forget('universities');    

        return response()->json(['message' => 'University deleted successfully']);
    }

    
}
