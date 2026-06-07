<?php

namespace App\Http\Controllers;

use App\Models\SystemConfi;
use Illuminate\Http\Request;

class SystemConfiController extends Controller
{
    /*
      Display a listing of the resource.
     */
    public function index()
    {
         return response()->json(SystemConfi::all());
    }

    /*
      Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /*
      Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
                $request->validate([
            'longitude' => 'required',
            'latitude' => 'required',
            'school_name' => 'required',
            'school_address' => 'required',
            'school_contact' => 'required'
        ]);

        $data = SystemConfi::create($request->all());

        return response()->json([
            'message' => 'Record Created Successfully',
            'data' => $data
        ], 201);
    }
    /*
     Display the specified resource.
     */
    public function show($id)
    {
        $data = SystemConfi::find($id);

        if (!$data) {
            return response()->json([
                'message' => 'Record Not Found'
            ], 404);
        }

        return response()->json($data);
    }
    /*
      Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data = SystemConfi::find($id);

        if (!$data) {
            return response()->json([
                'message' => 'Record Not Found'
            ], 404);
        }

        return response()->json([
        'message' => 'Record Found',
        'data' => $data
    ]);
    }

    /*
      Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = SystemConfi::find($id);

        if (!$data) {
            return response()->json([
                'message' => 'Record Not Found'
            ], 404);
        }

        // $request->validate([
        //     'longitude' => 'required',
        //     'latitude' => 'required',
        //     'school_name' => 'required',
        //     'school_address' => 'required',
        //     'school_contact' => 'required'
        // ]);

        $data->update($request->all());

        return response()->json([
            'message' => 'Record Updated Successfully',
            'data' => $data
        ]);
    }

    /*
      Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $data = SystemConfi::find($id);

        if (!$data) {
            return response()->json([
                'message' => 'Record Not Found'
            ], 404);
        }

        $data->delete();

        return response()->json([
            'message' => 'Record Deleted Successfully'
        ]);
    }
}
