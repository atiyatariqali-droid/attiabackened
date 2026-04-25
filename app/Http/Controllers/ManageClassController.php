<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManageClass;

class ManageClassController extends Controller
{
      function list(){
        return ManageClass::all();
      }
    /**
     * Store a newly created resource in storage.
     */
    public function addClass(Request $request)
    {
        $manageClass = new ManageClass();
        $manageClass->name = $request->name;
        $manageClass->status = $request->status;
        if($manageClass->save()){
            return ["result" => "Class added successfully"];
        }
        else{
            return ["result" => "Failed to add class"];
        }
    }

    

    /**
     * Display the specified resource.
     */
    // public function show(ManageClass $manageClass)
    // {
    //     return ManageClass::all();
    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function editClass($id)
    {
        $manageClass= ManageClass::find($id);
        return $manageClass;
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateClass(Request $request, $id)
    {
         $manageClass = ManageClass::find($id);
         $manageClass->name = $request->name;
         $manageClass->status = $request->status;
        if($manageClass->save()){
            return [
                "result" => "Class record updated successfully"
            ];
        }
        else{
            return [
                "result" => "Class record not updated"
            ];
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteClass($id)
    {
        $manageClass = ManageClass::destroy($id);
        if($manageClass){
            return [
                "result" => "Class record deleted successfully"
            ];
        }
        else{
            return [
                "result" => "Class record not deleted"
            ];
        }
    }

    //Search class by name

    function searchClass($name){
        $class = ManageClass::where("name", "like", "%$name%")->get();
        if($class){
            return["result" => $class];
        }
        else{
            return["result" => "Class record not found"];
        }
    }  
}
