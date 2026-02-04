<?php

namespace App\Http\Controllers\Admin;

use Session;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function editProfile(){
        return view('admin.profile.editprofile');
    }

    // Update Admin Profile
    public function updateProfile(Request $request){
        
        $request->validate([
            'username' => 'required|max:100',
            'email' => 'required|email',
            'name' => 'required|max:100',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        $adminprofile = Admin::where('id',$request->adminId)->first();
            
        if($request->hasFile('image')){
           @unlink('assets/front/img/'. $adminprofile->image);
           $file = $request->file('image');
           $extension = $file->getClientOriginalExtension();
           $image = 'adminProfile_'.time().rand().'.'.$extension;
           $file->move('assets/front/img/', $image);
           $adminprofile->image = $image;
       }

       $adminprofile->username = $request->username;
       $adminprofile->email = $request->email;
       $adminprofile->name = $request->name;
       $adminprofile->save();

       $notification = array(
        'messege' => 'Admin Profile Updated successfully!',
        'alert' => 'success'
    );
    return redirect()->back()->with('notification', $notification);
    }

    // Edit Admin Password
    public function editPassword(){
        return view('admin.profile.changepass');
    }

    public function updatePassword(Request $request) {
        $messages = [
            'password.required' => 'The new password field is required',
            'password.confirmed' => "Password does'nt match"
        ];
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required|confirmed'
        ], $messages);
        // if given old password matches with the password of this authenticated user...
        if(Hash::check($request->old_password, Auth::guard('admin')->user()->password)) {
            $oldPassMatch = 'matched';
        } else {
            $oldPassMatch = 'not_matched';
        }
        if ($validator->fails() || $oldPassMatch=='not_matched') {
            if($oldPassMatch == 'not_matched') {
              $validator->errors()->add('oldPassMatch', true);
            }
            return redirect()->route('admin.editPassword')
                        ->withErrors($validator);
        }
  
        // updating password in database...
        $user = Admin::findOrFail(Auth::guard('admin')->user()->id);
        $user->password = bcrypt($request->password);
        $user->save();
  
        $notification = array(
            'messege' => 'Password changed successfully!',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
      }
}
