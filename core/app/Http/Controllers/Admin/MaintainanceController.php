<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceSetting;

class MaintainanceController extends Controller
{
    public function maintainance_settings()
    {
        $settings = MaintenanceSetting::orderBy('id', 'desc')->get();
        return view('admin.maintainance.maintainance', ['title' => 'Maintenance settings', 'settings' => $settings]);
    }
    
    public function add_maintainance_settings()
    {
        return view('admin.maintainance.add_maintainance', ['title' => 'Add Maintenance settings']);
    }
    
    public function store_maintainance_settings(Request $request)
    {
        $rs = MaintenanceSetting::create([
            'subject' => $request->subject,
            'date' => $request->date,
            'from_time' => $request->from_time,
            'to_time' => $request->to_time,
            'message' => $request->message,
        ]);
        
        return redirect()->route('admin.maintainance.setting',app()->getLocale())->with('success', 'Settings Added Successfully!');
    }
    
    public function edit_maintainance_settings($ln, $id)
    {
        $setting = MaintenanceSetting::where('id', $id)->first();
        return view('admin.maintainance.edit_maintainance', ['title' => 'Edit Maintenance settings', 'settings' => $setting]);
    }
    
    public function update_maintainance_settings(Request $request, $ln, $id)
    {
        $rs = MaintenanceSetting::where('id', $id)->update([
            'subject' => $request->subject,
            'date' => $request->date,
            'from_time' => $request->from_time,
            'to_time' => $request->to_time,
            'message' => $request->message,
        ]);
        
        return redirect()->route('admin.maintainance.setting',app()->getLocale())->with('success', 'Settings Updated Successfully!');
    }
    
    public function delete_maintainance_settings($ln, $id)
    {
        $setting = MaintenanceSetting::where('id', $id)->delete();
        return redirect()->route('admin.maintainance.setting',app()->getLocale())->with('success', 'Settings Deleted Successfully!');
    }
}