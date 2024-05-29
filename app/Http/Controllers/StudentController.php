<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StudentController extends Controller
{
    public function index()
    {
        $students = DB::table('student')->get();
        if ($students->isEmpty()) {
            return response()->json(['message' => 'No Student found'], 404);
        }

        return response()->json($students);
    }

    public function show($id)
    {        
        $student = DB::table('student')->where('student_id', $id)->first();

        if (!$student) {
            return response()->json(['message' => 'University not found'], 404);
        }

        $university = DB::table('university_student')
            ->join('university', 'university_student.university_id', '=', 'university.university_id')
            ->where('university_student.student_id', $id)
            ->select('university.*')
            ->get();

        if (!$university->isEmpty()) {
            $student->university = $university;
        }

        return response()->json($student);
    }

    public function store(Request $request)
    {
        try {       
                       
            $request->validate([
                'name' => 'required|string|unique:student,name',
            ]);    
            
            $name = $request->input('name');
            $student_id = DB::table('student')->insertGetId(['name' => $name]);
            
            // Check for university_id in the request
            if ($request->has('university_id')) {
                
                $university_ids = $request->input('university_id');
                
                // Is an array
                if (!is_array($university_ids)) {
                    $university_ids = [$university_ids];
                }                
                // Remove duplicate 
                $university_ids = array_unique($university_ids);

                // bulk insert               
                foreach ($university_ids as $university_id) {

                    // Check exists 
                    $university = DB::table('university')->where('university_id', $university_id)->first();
                    
                    if ($university) {
                        $insertData[] = [
                            'university_id' => $university_id,
                            'student_id' => $student_id
                        ];
                    }
                    
                }           
                                
                // Insert university_student 
                if (!empty($insertData)) {
                    try {
                        DB::table('university_student')->insert($insertData);
                    } catch (\Exception $e) {
                        \Log::error('An error occurred while inserting into university_student', ['error' => $e->getMessage()]);
                        return response()->json(['message' => 'An error occurred while inserting the university-student relationship', 'error' => $e->getMessage()], 500);
                    }
                }
            } //
            
            return response()->json(['message' => 'Student inserted successfully'], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            \Log::error('An error occurred while inserting the student', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred while inserting the student', 'error' => $e->getMessage()], 500);
        }
    }



    public function update(Request $request, $id)
    {
        try {          

            // name update
            $rules = ['name' => 'nullable|string|unique:student,name,' . $id . ',student_id'];
            $this->validate($request, $rules);
            
            $name = $request->input('name');
            $updateData = [];
            if (!is_null($name)) {
                $updateData['name'] = $name;
            }
            
            if (!empty($updateData)) {
                $updated = DB::table('student')->where('student_id', $id)->update($updateData);
                if (!$updated) {
                    return response()->json(['message' => 'Student not found or no changes made'], 404);
                }
            }

            //  university_id updates
            if ($request->has('university_id')) {
                $university_ids = $request->input('university_id');
                if (!is_array($university_ids)) {
                    $university_ids = [$university_ids];
                }
                $university_ids = array_unique($university_ids);

                // Start 
                DB::beginTransaction();
                try {
                    // Delete existing 
                    DB::table('university_student')->where('student_id', $id)->delete();
                    
                    // Prepare insert data
                    $insertData = [];
                    foreach ($university_ids as $university_id) {
                        $university = DB::table('university')->where('university_id', $university_id)->first();
                        if ($university) {
                            $insertData[] = [
                                'university_id' => $university_id,
                                'student_id' => $id
                            ];
                        }
                    }
                    
                    // Insert new university_student records
                    if (!empty($insertData)) {
                        DB::table('university_student')->insert($insertData);
                    }
                    
                    // Commit 
                    DB::commit();
                } catch (\Exception $e) {

                    // Rollback 
                    DB::rollBack();
                    \Log::error('An error occurred while updating the university-student relationship', ['error' => $e->getMessage()]);
                    return response()->json([
                        'message' => 'An error occurred while updating the university-student relationship',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }
            
            Cache::forget('student_' . $id);
            return response()->json(['message' => 'Student updated successfully'], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('An error occurred while updating the student', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while updating the student',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }



    public function destroy($id)
    {        
        $university = DB::table('student')->where('student_id', $id)->first();

        if (!$university) {
            return response()->json(['message' => 'Student not found'], 404);
        }    

        DB::table('student')->where('student_id', $id)->delete();    
        Cache::forget("student_{$id}");
        Cache::forget('students');    

        return response()->json(['message' => 'Student deleted successfully']);
    }




}
