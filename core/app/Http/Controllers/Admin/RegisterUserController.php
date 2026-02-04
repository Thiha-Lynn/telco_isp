<?php

namespace App\Http\Controllers\Admin;
use App\Helpers\Helper;
use App\Models\User;
use App\Models\Admin;
use App\Models\Package;
use App\Models\SubCompany;
use App\Models\Billpaid;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use DB;
use Illuminate\Support\Facades\Hash;
use Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\StatusDescription;
class RegisterUserController extends Controller
{
    public function __construct()
    {
        $this->helper  = new Helper();
    }
    public function index()
    {
        
        $users = Admin::where('role_id','!=',1)->get();
        
        return view('admin.register_user.index',compact('users'));
    }
    public function getform()
    {
        $users = Admin::paginate(10);
        $role =  DB::table('role')->get();
        $sub_com   = SubCompany::all();
        $status = json_decode($this->helper->get_status());
        //dd($status);
        return view('admin.register_user.add_user',compact('users','role','status','sub_com'));
    }
    public function storeuser(Request $request)
    {
       $rules = array(
                'username'         => 'required',
                'email'        => 'unique:admins,email',
                'phone'        => 'unique:admins,phone',
                'password'        => 'unique:admins,phone',
            );

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
        {
            return back()->withErrors($validator)->withInput();
        }else
        { 
        //  echo $this->helper->make_slug("Shubha Kumar");
        //  die;
        $role = Role::where('id',$request->role_id)->first();
        $insert = Admin::Create([
             'uniqid'  => uniqid(),
             'username'=> $this->helper->make_slug($request->username),
             'role_id' => $request->role_id,
             'name'    => $request->username,
             'email'   => $request->email, 
             'phone'   => $request->phone, 
            // 'sub_company'   => $request->subcompany, 
             'password'      =>  Hash::make($request->password),
             'user_status'   => $request->status, 
            ]);
          
        return redirect('en/admin/register/users')->with('message', 'User Added Successfully!');
  
        }
    }
    
    public function delete($ln,$id)
    {
        $user = Admin::findOrFail($id);
        $user   = Admin::where('id',$id)->delete();
        return redirect()->back()->with('message','Staff Delete Successfully!');
    }
    
    
    public function edit_role($ln,$id)
    {
        $user = DB::table('admins')->where('id',$id)->first();
        $role =  DB::table('role')->get();
        return view('admin.register_user.edit_user',['user'=>$user,'role'=>$role]);
    }
    
    public function edit(Request $request)
    {
       
         $query = DB::table('admins')->where('id',$request->input('id'))->update([
         'username'        =>  $request->username??'',
        'username'        =>  $request->username??'',
        'email'     => $request->email??'',
        'role_id'   => $request->role_id??'',
        'password'      =>  Hash::make($request->confpassword),
        'phone'           => $request->phone??'',
        'user_status'     => $request->status??'',
            ]);
        
        
        return redirect('en/admin/register/users')->with('message', 'User Update Successfully!');



    }
    
    public function view($ln,$id)
    {
       
        $user = Admin::findOrFail($id);
        $package = Package::find($user->activepackage);
        $bills = Billpaid::with('user', 'package')->where('user_id', $id)->orderBy('id', 'DESC')->paginate(10);

    
        return view('admin.register_user.details',compact('user','package', 'bills'));

    }

    public function package_buy(){
        $activeusers = User::whereNotNull('activepackage')->get();
        return view('admin.register_user.package-buy', compact('activeusers'));
    }

    public function package_not_buy(){
        $dactiveusers = User::where('activepackage', NULL)->get();

        return view('admin.register_user.package-not-buy', compact('dactiveusers'));
    }


    public function userban(Request $request)
    {

        $user = User::findOrFail($request->user_id);
        $user->update([
            'status' => $request->status,
        ]);

        Session::flash('success', $user->username.' status update successfully!');
        return back();



    }
}
