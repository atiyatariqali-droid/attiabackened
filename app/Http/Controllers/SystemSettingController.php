<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;

class SystemSettingController extends Controller
{
    // GET /api/settings
    public function index() {
        $settings = SystemSetting::all();
        return response()->json(['success' => true, 'data' => $settings]);
    }

    // POST /api/settings
    public function store(Request $request) {
        $request->validate([
            'key' => 'required|unique:system_settings,key',
            'value' => 'required',
            'type' => 'required|in:text,number,boolean,json'
        ]);

        $setting = SystemSetting::create($request->all());
        return response()->json(['success' => true, 'data' => $setting], 201);
    }

    // GET /api/settings/{id}
    public function show($id) {
        $setting = SystemSetting::find($id);
        if(!$setting) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        return response()->json(['success' => true, 'data' => $setting]);
    }

    // PUT /api/settings/{id}
    public function update(Request $request, $id) {
        $setting = SystemSetting::findOrFail($id);
        $setting->update([
            'value' => $request->value
        ]);
    
        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    // DELETE /api/settings/{id}
    public function destroy($id) {
        $setting = SystemSetting::find($id);
        if(!$setting) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        
        $setting->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }
}