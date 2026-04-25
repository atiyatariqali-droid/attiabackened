<?php

namespace App\Http\Controllers;

 
use Illuminate\Http\Request;
use App\Models\Teachers;

class TeachersController extends Controller
{
    //
    function list(){
        return Teachers::all();
    }

  
//add teacher

    function addTeacher(Request $request){
        $teacher = new Teachers();
        $teacher->name = $request->name;
        $teacher->email = $request->email;
        $teacher->password = bcrypt($request->password);
        $teacher->phone = $request->phone;
        if($teacher->save()){
            return ["result" => "Teacher added successfully"];
    }
        else{
            return ["result" => "Failed to add teacher"];
        }
    }

              // editTeacher 
    function editTeacher($id){  
        $teacher = Teachers::find($id);
        return $teacher;
    }  

      //update updateTeacher

    function updateTeacher(Request $request, $id){

        $teacher = Teachers::find($id);

        if(!$teacher){
            return response()->json([
                "error" => "Teacher not found"
            ], 404);
        }

        // Validate request
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'nullable|min:6',
            'phone' => 'nullable'
        ]);

        // Prepare data
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone
        ];

        // Only update password if provided
        if($request->password){
            $data['password'] = bcrypt($request->password);
        }

        $teacher->update($data);

        return response()->json([
            "result" => "Teacher updated successfully"
        ]);
    }


                     // Delete teacher
    function deleteTeacher($id){  
        $teacher = Teachers::destroy($id);
        if($teacher){
            return [
                "result" => "Teacher record deleted successfully"
            ];
        }
        else{
            return [
                "result" => "Teacher record not deleted"
            ];
        }
    }  
      //Search teacher by name
      
    function searchTeacher($name){
        $teacher = Teachers::where("name", "like", "%$name%")->get();
        if($teacher){
            return["result" => $teacher];
        }
        else{
            return["result" => "Teacher record not found"];
        }
    }  
}
