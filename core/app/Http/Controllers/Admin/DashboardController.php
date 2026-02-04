<?php

namespace App\Http\Controllers\Admin;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\Setting;
use App\Models\Client;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\About;
use App\Models\Branch;
use App\Models\Package;
use App\Models\Product;
use App\Models\Scategory;
use App\Models\Team;
use App\Models\PaymentQuery;
use App\Models\Testimonial;
use App\Models\PaymentGatewey;
use App\Models\User;
use App\Models\FaultReportQuery;
use App\Models\UserQuery;
use App\Models\MbtBindUser;
use App\Models\PermissionModel;
use App\Models\Binduser;

use DB;
use App\Models\SubCompany;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Notification;
use App\Models\PaymentNew;
use App\Models\UserDevice;

class DashboardController extends Controller
{
    public function __construct()
    {
        $settings = Setting::where('id', '1')->first();
        $this->base_url = $settings->ip_address;
    }
    
    public function PostFunction($api_url,$mbt_params)
    {      
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_POST, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$mbt_params);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        $output = curl_exec($ch);
        $result = json_decode($output, true);
		$response = $result['data']['access_token'];
        return $response;
    }
    

    public function dashboard()
    {
        //dd(Auth::guard('admin')->user()->get_role());
        $img = Auth()->guard('admin')->user()->image??'';
        if($img == '')
        {
            $up = Admin::where('id',Auth()->user()->id)->update(['image'=>'header_logo_162426094536756743.png']);
        }
        $monthly_pay    = PaymentNew::whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->sum('total_amt');
        $today_pay      = PaymentNew::whereDate('created_at', Carbon::today())->sum('total_amt');
        
        $year_pay       = PaymentNew::select(DB::raw("(COUNT(*)) as count"),DB::raw("YEAR(created_at) as year"))->sum('total_amt');
        
        for ($i = 1; $i <= 12; $i++)
        {
            $current_data[]   = PaymentNew::whereYear('created_at', date('2021'))->whereMonth('created_at', $i)->sum('total_amt');
            $current_next_year[]   = PaymentNew::whereYear('created_at', date('2022'))->whereMonth('created_at', $i)->sum('total_amt');
            $current_next_year23[]   = PaymentNew::whereYear('created_at', date('2023'))->whereMonth('created_at', $i)->sum('total_amt');
            $current_next_year24[]   = PaymentNew::whereYear('created_at', date('2024'))->whereMonth('created_at', $i)->sum('total_amt');
            $current_next_year25[]   = PaymentNew::whereYear('created_at', date('2025'))->whereMonth('created_at', $i)->sum('total_amt');
       }     
        // DB::table('users')->select('id','name')->where('id',1)->get();
       //dd($current_data);
        // For User
        $daily_user     = User::whereDate('created_at', Carbon::today())->count();
        //$daily_user     = User::whereDate('created_at', Carbon::today())->count();
        
        $user           = User::all();
        $packages       = Package::all();
        $latestpackages = Package::orderBy('id', 'DESC')->limit(10)->get();
        $service        = Service::all();
        $branch         = Branch::all();
        $product        = Product::all();
        $blogs          = Blog::all();
        $latestblogs    = Blog::orderBy('id', 'DESC')->limit(10)->get();
        $testimonial    = Testimonial::all();
        $team           = Team::all();
        return view('admin.dashboard', compact('user', 'packages','service', 'current_data','current_next_year','current_next_year23','current_next_year24','current_next_year25','branch', 'blogs', 'daily_user','testimonial', 'team', 'year_pay','latestpackages', 'latestblogs', 'today_pay','product','monthly_pay'));
    }
    
    public function get_bind_payment_user()
    {
        $data['bind_user'] = DB::table('mbt_bind_user')->get();
        $data['sub_com']   = SubCompany::all();
        return view('admin.payment.bind_user',$data);
    }
    
    // public function payment_query(Request $request)
    // {
    //           $date_from_new = date('Y-m-d', strtotime('-365 days', time()));
    //           $date_to_new   = date('Y-m-d', strtotime(date('Y-m-d')));  
    //           $data['user']      = User::all();
    //           $data['sub_com']   = SubCompany::all();
    //           $data['payment']   =DB::table('payment_new')
    //                               ->where('admin_status',1)
    //                               ->orWhere('admin_status',2)
    //                               ->orderBy('created_at', 'DESC')
    //                               ->paginate(10);
    //                         //dd($data['payment']);
    //     $data['payment_gateway']   = PaymentGatewey::where('status',1)->whereIn('id', [8,9,10,11,12])->get();
        
    //     return view('admin.payment.index',$data);
    // }
    
    public function payment_query(Request $request)
    {
        $settings = Setting::where('id', '1')->first();
        $data['base_url'] = $settings->ip_address ?? '';
        $data["access_token"] = ""; // Initialize access_token
        
        if (!empty($data['base_url'])) {
            $url = $data['base_url']."api/v1/auth/get-access-token"; 
            $headerArray = array("Content-type:application/json","Accept:application/json");
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 0); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $output = curl_exec($ch);
            curl_close($ch);
            
            if ($output !== false) {
                $decode_token = json_decode($output, true);
                if (isset($decode_token['data']['access_token'])) {
                    $data['access_token'] = $decode_token['data']['access_token'];
                }
            }
        }
        
        $date_from_new = date('Y-m-d', strtotime('-365 days', time()));
        $date_to_new   = date('Y-m-d', strtotime(date('Y-m-d')));  
        $data['user']      = User::all();
        $data['sub_com']   = SubCompany::all();
        $data['payment']   = DB::table('payment_new')->where('admin_status',1)->orWhere('admin_status',2)->orderBy('trans_date', 'DESC')->paginate(10);
        $data['payment_gateway']   = PaymentGatewey::where('status',1)->whereIn('id', [8,9,10,11,12])->get();
        
        $allpayment   = DB::table('payment_new')->where('admin_status',1)->orWhere('admin_status',2)->get();
        
        $total_pay = 0;
        foreach($allpayment as $pay){
            $total_pay += $pay->total_amt;
        }
        
        $data['total_pay'] = $total_pay;
        
        return view('admin.payment.index',$data);
    }
    
    public function payment_user_detail($ln,$id)
    {
        $mbt_params = array(
            "Content-type:application/json",
            "Accept:application/json"
        );

        $api_url = $this->base_url."api/v1/auth/get-access-token";

        $token  = $this->PostFunction($api_url,$mbt_params);
      
        $data['users'] = $users = User::where('bind_user_id',$id)->first();
        
        $data['bind_users'] = $bind_users = MbtBindUser::where('er_id', $id)->first();
     
        $new_url = $this->base_url."api/v1/package/users-packages?access_token={$token}&user_name={$bind_users->user_name}";
	    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $new_url);
		curl_setopt($ch, CURLOPT_POST, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$mbt_params);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 80);
	    $output_new = curl_exec($ch);
	    $result_new = json_decode($output_new,true);
	    if(!empty($result_new['data'])){
	        $response_new = $result_new['data'][0];
	    }else{
	        $response_new = 'N/A';
	    }
	    
	    $data['response'] = $response_new;
        
        return view('admin.payment.payment_user_detail',$data);
    }

  
        // //dd($expirey_from_new);
        // $data['user']      = User::all();
        // $data['sub_com']   = SubCompany::all();
        // $data['payment']   = PaymentQuery::where($conditions)
        //                      ->where('admin_status',1)
        //                     ->where('total_amt', '>=', $pay_amt_from)
        //                     ->where('total_amt', '<=', $pay_amt_to)
        //                     ->where('total_amt', '!=', '0.00')
        //                     ->whereDate('created_at', '>=', $expirey_from_new)
                            
        //                     ->get();
        // $data['payment_gateway']   = PaymentGatewey::where('status',1)->whereIn('id', [8,9,10,11])->get();
        
        // return view('admin.payment.index',$data);
    
    
    // public function search_payment_query(Request $request)
    // {
    //     // dd($request->all());
       
    //   $user_account = $request->input('user_account');
    //   $sub_com_id= $request->input('sub_com_id');
    //   $Pay_method =$request->input('Pay_method');
    //   $result_status= $request->input('result_status');
      
    //   $pay_date_from = $request->input('pay_id');
    //   $pay_date_end =  $request->input('pay_result');
       
    //     $pay_date_froms = (int)$pay_date_from;
    //     $pay_date_ends   = (int)$pay_date_end;
        
    //     $expiery_date_from = $request->input('expiery_date_from');
    //     $expiery_date_end = $request->input('expiery_date_end');
        
        
    //     $expirey_from_new = date('Y-m-d', strtotime($expiery_date_from));
    //     $expirey_to_new   = date('Y-m-d', strtotime($expiery_date_end));
          
              
        
    //         $data['payment_gateway']   = PaymentGatewey::where('status',1)->whereIn('id', [8,9,10,11])->get(); 
    //         $data['user']      = User::all();
    //         $data['sub_com']   = SubCompany::all();
            
            
    //         $query = PaymentNew::query();
            
    //         //$query = $query->where('admin_status', '=', '1');

    //         if ($user_account) {
    //             $query = $query->where('payment_user_name', '=', $user_account);
    //         }
    //         if ($sub_com_id) {
    //             $query = $query->where('sub_com_id', '=', $sub_com_id);
    //         }
    //         if ($Pay_method) {
    //             $query = $query->where('payment_method', '=', $Pay_method);
    //         }
    //         if ($result_status) {
    //             $query = $query->where('admin_status', '=', $result_status);
    //         }
    //         if ($pay_date_froms) {
                
    //             $query = $query->where('total_amt', '>=', $pay_date_froms); 
                
    //         }
    //         if ($pay_date_ends) {
    //             $query = $query->where('total_amt', '<=', $pay_date_ends);
    //         }
    //         if ($expiery_date_from) {
    //             //die('123');
    //             $query = $query->whereDate('trans_date', '>=', $expirey_from_new);
    //         }
    //         if ($expiery_date_end) { 
    //             //die('456');
    //             $query = $query->whereDate('trans_date', '<=', $expirey_to_new);
    //         }
            
    //         $data['payment'] =  $payment = $query->paginate(10);
            
    //          //dd($payment);
             
    //         $payment_all = PaymentNew::get();
             
    //         $total_pay = 0;
    //         foreach($payment_all as $pay){
    //             $total_pay += $pay->total_amt;
    //         }
            
    //         // dd($total_pay);
            
    //         $data['total_pay'] = $total_pay;
            
    //         return view('admin.payment.search_payment',$data);
          
      
    // }
    
    public function search_payment_query(Request $request)
    {
        // dd($request->all());
        
        $settings = Setting::where('id', '1')->first();
        $data['base_url'] = $settings->ip_address ?? '';
        $data["access_token"] = ""; // Initialize access_token
        
        if (!empty($data['base_url'])) {
            $url = $data['base_url']."api/v1/auth/get-access-token"; 
            $headerArray = array("Content-type:application/json","Accept:application/json");
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 0); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $output = curl_exec($ch);
            curl_close($ch);
            
            if ($output !== false) {
                $decode_token = json_decode($output, true);
                if (isset($decode_token['data']['access_token'])) {
                    $data['access_token'] = $decode_token['data']['access_token'];
                }
            }
        }
       
      $user_account = $request->input('user_account');
      $sub_com_id= $request->input('sub_com_id');
      $Pay_method =$request->input('Pay_method');
      $result_status= $request->input('result_status');
      
      $pay_date_from = $request->input('pay_id');
      $pay_date_end =  $request->input('pay_result');
       
        $pay_date_froms = (int)$pay_date_from;
        $pay_date_ends   = (int)$pay_date_end;
        
        $expiery_date_from = $request->input('expiery_date_from');
        $expiery_date_end = $request->input('expiery_date_end');
        
        
        $expirey_from_new = date('Y-m-d', strtotime($expiery_date_from));
        $expirey_to_new   = date('Y-m-d', strtotime($expiery_date_end));
          
              
        
            $data['payment_gateway']   = PaymentGatewey::where('status',1)->whereIn('id', [8,9,10,11])->get(); 
            $data['user']      = User::all();
            $data['sub_com']   = SubCompany::all();
            
            
            $query = PaymentNew::query();
            
            //$query = $query->where('admin_status', '=', '1');

            if ($user_account) {
                $query = $query->where('payment_user_name', '=', $user_account);
            }
            if ($sub_com_id) {
                $query = $query->where('sub_com_id', '=', $sub_com_id);
            }
            if ($Pay_method) {
                $query = $query->where('payment_method', '=', $Pay_method);
            }
            if ($result_status) {
                $query = $query->where('admin_status', '=', $result_status);
            }
            if ($pay_date_froms) {
                
                $query = $query->where('total_amt', '>=', $pay_date_froms); 
                
            }
            if ($pay_date_ends) {
                $query = $query->where('total_amt', '<=', $pay_date_ends);
            }
            if ($expiery_date_from) {
                //die('123');
                $query = $query->whereDate('trans_date', '>=', $expirey_from_new);
            }
            if ($expiery_date_end) { 
                //die('456');
                $query = $query->whereDate('trans_date', '<=', $expirey_to_new);
            }
            
            $allpayment   = $query->orderBy('trans_date', 'DESC')->get();
            
            $data['payment'] =  $payment = $query->paginate(10);
            
            $total_pay = 0;
            foreach($allpayment as $pay){
                $total_pay += $pay->total_amt;
            }
            
            $data['total_pay'] = $total_pay;
            
            return view('admin.payment.search_payment',$data);
          
      
    }
    
      public function jquery_filter(Request $request)
    {    echo "okay";
        
         // $ajax_search=DB::table('sub_company_list')->where('company_name',$request->input('sub_com_id'))->get();
        //   $SubCompany= $ajax_search[0]->company_name;
        //  // print_r($SubCompany);
        // 
       //   $paymethod=DB::table('payment_gateweys')->where('title',$request->input('method_id'))->get();
        //   $paymethods=$paymethod[0]->title; 
        // //  print_r($paymethods); 
        
              //  $paymethodss=DB::table('payment_query')->where('sub_com_id',$request->input('method_id'))->get();


    //      $array1 = json_decode(json_encode($ajax_search), true);
    //      $array2 = json_decode(json_encode($paymethod), true);
         
    //     $services =  array_merge($array1,$array2);
    //     echo "<pre>";
    //     print_r($services);
    //   //  print_r($array2);
    //     echo "</pre>";
          
    //       // $pay_result= $request->input('pay_result');
          
    //     //   $software = Software::join('orders','orders.product_details','=','software.id')
    //     //                  ->where('software.status', 1)
    //     //                  ->where('orders.order_id',$order)->get();
                         
                         
    //             // $shares = DB::table('payment_query')
    //             //                     ->join('sub_company_list', 'sub_company_list.sub_com_id', '=', 'payment_query.sub_com_id')
    //             //                     ->where('payment_query.sub_com_id', '=', 1)
    //             //                     ->get();  
    //             //                     echo "<pre>";
    //             //                     print_r($shares);
    //             //                     echo "<pre>";
 }
    
    
    
    public function update_payment_query(Request $request)
    {   $update = PaymentNew::where('id',$request->query_id)->update(['status'=>$request->status]);
       return redirect()->back()->with('message','Status Update Successfully!');
    }
    public function fault_query(Request $request)
    {
        $data['fault']  = DB::table('fault_report_query')->orderBy('created_at','desc')->paginate(10);
                                        
        $data['sub_com']   = SubCompany::all();
        $data['user']   = User::all();
        return view('admin.fault.index',$data);
    }
    
     public function search_fault_query(Request $request)
    {
       
        $mbt_account_id = $request->input('mbt_account_id');
        $sub_company = $request->input('sub_company');
       
        $action = $request->input('action');
          
        $pay_date_from = $request->input('report_start_date');
        $pay_date_end   = $request->input('report_end_date');
        
        $date_from_new = date('Y-m-d', strtotime($pay_date_from));
        $date_to_new   = date('Y-m-d', strtotime($pay_date_end));
        
           $query = FaultReportQuery::query();
            
            if ($mbt_account_id) {
                $query = $query->where('mbt_id', '=', $mbt_account_id);
            }
            if ($sub_company) {
                $query = $query->where('sub_com_id', '=', $sub_company);
            }
            if ($action) {
                $query = $query->where('fault_status', '=', $action);
            }
 
            if ($pay_date_from) {
                $query = $query->whereDate('created_at', '>=', $date_from_new);
            }
            if ($pay_date_end) { 
                $query = $query->whereDate('created_at', '<=', $date_to_new);
            }
            
            $data['fault']  = $query->paginate(10);
            
            $data['sub_com']   = SubCompany::all();
            $data['user']   = User::all();
            return view('admin.fault.search_fault_table',$data);
    }
    
    public function update_fault_query(Request $request)
    {  

        $users=$request->input('users_fault');
        $apllyer_user_id=$request->input('apllyer_user_id');
        $usersss=DB::table('users')->where('bind_user_id',$users)->get();
        $notification_user= $usersss[0]->bind_user_id;
        $usersssss=DB::table('mbt_bind_user')->where('er_id',$notification_user)->get();
        $noti_id=$usersssss[0]->user_name;
        
        $language_notifies=UserDevice::where('user_id',$apllyer_user_id)->get();
        foreach($language_notifies as $language_notifi){           
            if($request->status == 0 && $language_notifi->language_id == 0)
             { 
               
             $status = "your fault query status changed current status is ,Accept";
             DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 0 && $language_notifi->language_id == 1)
             { 
                $status = "您的故障查询状态改变了当前状态是，接受";
                DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 0 && $language_notifi->language_id == 2)
             { 
                $status = "သင်၏အမှားမေးမြန်းမှုအခြေအနေသည် ပြောင်းလဲသွားသော လက်ရှိအခြေအနေမှာ လက်ခံပါသည်။";
                DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 1 && $language_notifi->language_id == 0)
             { 
               
             $status = "your fault query status changed current status is ,Processing";
             DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 1 && $language_notifi->language_id == 1)
             { 
                $status = "您的故障查询状态已更改为当前状态，正在处理中";
                DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 1 && $language_notifi->language_id == 2)
             { 
                $status = "သင်၏အမှားမေးမြန်းမှုအခြေအနေသည် လက်ရှိအခြေအနေ၊ လုပ်ဆောင်နေပါသည်။";
                DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 2 && $language_notifi->language_id == 0)
             { 
               
             $status = "your fault query status changed current status is ,Complete";
             DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 2 && $language_notifi->language_id == 1)
             { 
                $status = "您的故障查询状态改变了当前状态是，完成";
                DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 2 && $language_notifi->language_id == 2)
             { 
                $status = "သင်၏အမှားမေးမြန်းမှုအခြေအနေသည် လက်ရှိအခြေအနေ၊ ပြီးပြည့်စုံပြီဟု ပြောင်းလဲထားသည်။ခံပါသည်။";
                DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 3 && $language_notifi->language_id == 0)
             { 
               
             $status = "your fault query status changed current status is ,No Accept";
             DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 3 && $language_notifi->language_id == 1)
             { 
                $status = "您的故障查询状态已更改为当前状态，不接受";
                DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }elseif($request->status == 3 && $language_notifi->language_id == 2)
             { 
                $status = "သင်၏အမှားမေးမြန်းမှုအခြေအနေသည် လက်ရှိအခြေအနေ၊ လက်ခံခြင်းမရှိပါ။";
                DB::table('notification')->insert([
                                        'account_id'           => $noti_id,
                                        'publish_info'  => $status,
                                        'install_user_id'=>$language_notifi->device_id
                                        ]);
             }
               
             else
             { 
                $status = "your fault query status changed current status is ,Complete";
             } 
             
           //$user = UserDevice::where('user_id', $request->apllyer_user_id)->first();
           //dd($user->device_id);
           Helper::SendFirebasembtnotification($language_notifi->fcm_token,$status);
        }
         
         
         
         
         
       
       $update = FaultReportQuery::where('id',$request->query_id)->update(['fault_status'=>$request->status]);
       return redirect()->back()->with('message','Status Update Successfully!');
    }
    public function install_query(Request $request)
    {
        $register_phone =  $_GET['Register_phone']??'0';
        $action         =  $_GET['action']??'12';
       
        $start_date     = $_GET['start_date']??'0';
        $end_date       = $_GET['end_date']??'0';
        $table          = 'user_query';
        $date           = Helper::get_first_and_last_date($table)??'';
        $decode = json_decode($date);
        if (($start_date !=0) && ($end_date !=0))
        { 
            //echo "ok"; die;
              $date_from_new = date('Y-m-d', strtotime($start_date));
              $date_to_new   = date('Y-m-d', strtotime($end_date));
              $data['sub_com']   = SubCompany::all();
              $data['user_query'] = UserQuery::whereDate('apply_date_start', '>=', $date_from_new)
                                          ->whereDate('apply_date_start', '<=', $date_to_new)
                                           ->orderBy('apply_id','desc')->paginate(10);
                $data['user']   = User::all();
                return view('admin.install.index',$data);

        }
         
        elseif(($register_phone !=0))
        { 
              $data['sub_com']   = SubCompany::all();
              $data['user_query'] = UserQuery::
                                          where('user_number','=',$register_phone)
                                           ->orderBy('apply_id','desc')->paginate(10);
                $data['user']   = User::all();
                return view('admin.install.index',$data);
        }
        
         elseif(($action !=12))
        { 
              $data['sub_com']   = SubCompany::all();
              $data['user_query'] = UserQuery::
                                          where('query_status','=',$action)
                                           ->orderBy('apply_id','desc')->paginate(10);
                $data['user']   = User::all();
                return view('admin.install.index',$data);
        }
        
        elseif(($register_phone !=0) && ($action !=0))
        { 
            //echo "ok"; die;
              $date_from_new = date('Y-m-d', strtotime($start_date));
              $date_to_new   = date('Y-m-d', strtotime($end_date));
              $data['sub_com']   = SubCompany::all();
              $data['user_query'] = UserQuery::
                                          where('user_number','=',$register_phone)
                                          ->where('query_status','=',$action)
                                           ->orderBy('apply_id','desc')->paginate(10);
                $data['user']   = User::all();
                return view('admin.install.index',$data);
        
        
        }
        
        elseif(($start_date !=0) && ($end_date !=0) && ($register_phone !=0))
        { 
            //echo "ok"; die;
              $date_from_new = date('Y-m-d', strtotime($start_date));
              $date_to_new   = date('Y-m-d', strtotime($end_date));
              $data['sub_com']   = SubCompany::all();
              $data['user_query'] = UserQuery::whereDate('apply_date_start', '>=', $date_from_new)
                                          ->whereDate('apply_date_start', '<=', $date_to_new)
                                          ->where('user_number','=',$register_phone)
                                          //->where('query_status','=',$action)
                                           ->orderBy('apply_id','desc')->paginate(10);
                $data['user']   = User::all();
                return view('admin.install.index',$data);
        }
        
        
          elseif(($register_phone !=0) && ($action !=0) && ($start_date !=0))
        { 
            //echo "ok"; die;
              $date_from_new = date('Y-m-d', strtotime($start_date));
              $data['sub_com']   = SubCompany::all();
              $data['user_query'] = UserQuery::whereDate('apply_date_start', '>=', $date_from_new)
                                          ->where('user_number','=',$register_phone)
                                          ->where('query_status','=',$action)
                                           ->orderBy('apply_id','desc')->paginate(10);
                $data['user']   = User::all();
                return view('admin.install.index',$data);
        }
        
       elseif(($register_phone !=0) && ($action !=0) && ($start_date !=0) && ($end_date !=0))
        { 
            //echo "ok"; die;
              $date_from_new = date('Y-m-d', strtotime($start_date));
              $date_to_new   = date('Y-m-d', strtotime($end_date));
              $data['sub_com']   = SubCompany::all();
              $data['user_query'] = UserQuery::whereDate('apply_date_start', '>=', $date_from_new)
                                          ->whereDate('apply_date_start', '<=', $date_to_new)
                                          ->where('user_number','=',$register_phone)
                                          ->where('query_status','=',$action)
                                           ->orderBy('apply_id','desc')->paginate(10);
                $data['user']   = User::all();
                return view('admin.install.index',$data);
        }
        
        
        
        else
        {
              $date_from_new = date('Y-m-d', strtotime('-365 days', time()));
              $date_to_new   = date('Y-m-d', strtotime(date('Y-m-d')));  
              $data['sub_com']   = SubCompany::all();
              $data['user_query'] = UserQuery::whereDate('apply_date_start', '>=', $date_from_new)
                                          ->whereDate('apply_date_start', '<=', $date_to_new)
                                          ->orderBy('apply_id','desc')->paginate(10);
              $data['user']   = User::all();
                return view('admin.install.index',$data);

        }
       
    }
    
    
    
public function search_install_query(Request $request)
    {
        
        $mbt_account_id = $request->input('Register_phone');
        $status = $request->input('action');
        
        //echo $status; die;
        
        $pay_date_from = $request->input('start_date');
        $pay_date_end = $request->input('end_date');
        
        $date_from_new = date('Y-m-d', strtotime($pay_date_from));
        $date_to_new   = date('Y-m-d', strtotime($pay_date_end));
        
           $query = UserQuery::query();
            
            if ($mbt_account_id) {
                $query = $query->where('user_number', '=', $mbt_account_id);
            }
            if ($status) {
                $query = $query->where('query_status', '=', $status);
            }
           
            if ($pay_date_from) {
                $query = $query->whereDate('apply_date_start', '>=', $date_from_new);
            }
            if ($pay_date_end) { 
                $query = $query->whereDate('apply_date_start', '<=', $date_to_new);
            }
            
             $data['user_query']  = $query->paginate(10);
            
                $data['user']   = User::all();
                
                return view('admin.install.search_query',$data);
    }
    
    
    public function update_install_query(Request $request)
    {
        $users=$request->input('users_hidden');
        $install_user_id=$request->input('install_user_id');
        $usersss=DB::table('users')->where('bind_user_id',$users)->get();
        if(count($usersss) > 0){
            $notification_user= $usersss[0]->bind_user_id;
            $usersssss=DB::table('mbt_bind_user')->where('er_id',$notification_user)->get();
            if(count($usersssss) > 0){
                $noti_id=$usersssss[0]->user_name;
                $language_notifies=UserDevice::where('user_id',$install_user_id)->get();
                
                foreach($language_notifies as $language_notifi){  
                    
                    if($request->status == 1 && $language_notifi->language_id == 0)
                     { 
                       
                     $status = "your install query status changed current status is ,Accept";
                     DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 1 && $language_notifi->language_id == 1)
                     { 
                        $status = "您的安装查询状态已更改，当前状态为，接受";
                        DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 1 && $language_notifi->language_id == 2)
                     { 
                        $status = "သင်၏ထည့်သွင်းမှုမေးမြန်းမှုအခြေအနေသည် ပြောင်းလဲသွားသော လက်ရှိအခြေအနေမှာ ၊ လက်ခံပါသည်";
                        DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 3 && $language_notifi->language_id == 0)
                     { 
                       
                     $status = "your install query status changed current status is ,Processing";
                     DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 3 && $language_notifi->language_id == 1)
                     { 
                        $status = "您的安装查询状态已更改，当前状态为，正在处理";
                        DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 3 && $language_notifi->language_id == 2)
                     { 
                        $status = "သင်၏ထည့်သွင်းမှုမေးမြန်းမှုအခြေအနေသည် လက်ရှိအခြေအနေ၊ လုပ်ဆောင်နေသည် ဟု ပြောင်းလဲထားသည်";
                        DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 4 && $language_notifi->language_id == 0)
                     { 
                       
                     $status = "your install query status changed current status is ,Complete";
                     DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 4 && $language_notifi->language_id == 1)
                     { 
                        $status = "您的安装查询状态已更改，当前状态为，完成";
                        DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 4 && $language_notifi->language_id == 2)
                     { 
                        $status ="သင်၏ထည့်သွင်းမှုမေးမြန်းမှုအခြေအနေသည် လက်ရှိအခြေအနေသို့ ပြောင်းလဲသွားသည်မှာ၊ ပြီးမြောက်ပြီဖြစ်သည်";
                        DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 2 && $language_notifi->language_id == 0)
                     { 
                       
                     $status = "your install query status changed current status is ,No Accept";
                     DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 2 && $language_notifi->language_id == 1)
                     { 
                        $status = "您的安装查询状态已更改为，不接受";
                        DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }elseif($request->status == 2 && $language_notifi->language_id == 2)
                     { 
                        $status = "သင်၏ ထည့်သွင်းမေးမြန်းမှု အခြေအနေသည် လက်ရှိအခြေအနေသို့ ပြောင်းလဲသွားသည်မှာ ၊ လက်ခံမည် မဟုတ်ပါ။";
                        DB::table('notification')->insert([
                                                'account_id'           => $noti_id,
                                                'install_user_id' =>$language_notifi->device_id,
                                                'publish_info'  => $status
                                                ]);
                     }
            
                     else
                     { 
                        $status = "your install query status changed current status is ,Complete";
                     } 
        
        
                  //$user = User::find($request->install_user_id);
                  //dd($user->device_id);
                  Helper::SendFirebasembtnotification($language_notifi->fcm_token,$status);
                }
            }
        }
        $data['user_query'] = UserQuery::where('apply_id',$request->query_id)->update(['query_status'=>$request->status]);
        return redirect()->back()->with('message','Status Update Successfully!');
    }
    public function user_query()
    {
        $userphone   = $_GET['userphone']??'0';
        $username   = $_GET['username']??'0';
        $acc_status   = $_GET['acc_status']??'0';
        
        $pay_date_from = $_GET['reg_date_str']??'0';
        $pay_date_end   = $_GET['reg_date_end']??'0';
        
        $date_from_new = date('Y-m-d', strtotime($pay_date_from));
        $date_to_new   = date('Y-m-d', strtotime($pay_date_end));
        
        $query = User::query();
        
        if ($userphone) {
            $query = $query->where('phone', '=', $userphone);
        }
        if ($username) {
            $query = $query->where('name', '=', $username);
        }
        if ($acc_status == 0){
            $query = $query->where('user_status', '=', $acc_status);
        }elseif ($acc_status == 1){
            $query = $query->where('user_status', '=', $acc_status);
        }
        if ($pay_date_from) {
            $query = $query->whereDate('created_at', '>=', $date_from_new);
        }
        if ($pay_date_end) { 
            $query = $query->whereDate('created_at', '<=', $date_to_new);
        }
        
        $data['bind_user'] = $query->paginate(10);
                            
        $data['sub_com']   = SubCompany::all();
        return view('admin.user.index',$data);
    }
    
    public function bind_user_query()
    {
       $data['bind_user'] = Binduser::orderBy('id','desc')->paginate(10);
       $data['sub_com']   = SubCompany::all();
       return view('admin.user.bind_user',$data);
    }
    
    public function search_bind_user(Request $request)
    {
        $register_phone = $request->input('register_phone');
      
        $sub_com        = $request->input('sub_com');
        $user_account   = $request->input('user_account');
        $account_status = $request->input('account_status');
       
        $reg_date_str   = $request->input('reg_date_str');
        $reg_date_end   = $request->input('reg_date_end');
        $bind_date_str  = $request->input('bind_date_str');
        $bind_date_end  = $request->input('bind_date_end');
     
        $reg_date_str_new   = date('Y-m-d', strtotime($reg_date_str));
        $reg_date_end_new   = date('Y-m-d', strtotime($reg_date_end));
        $bind_date_str_new = date('Y-m-d', strtotime($bind_date_str));
        $bind_date_end_new   = date('Y-m-d', strtotime($bind_date_end));
            
        $query = Binduser::query();
        
        $query = $query->select('users.*', 'bind_history.*')->join('users', 'users.id', '=', 'bind_history.user_id');
        $query = $query->select('sub_company_list.*', 'bind_history.*')->join('sub_company_list', 'sub_company_list.sub_com_id', '=', 'bind_history.sub_company');
        
        if ($register_phone) {
            $query = $query->where('users.phone', '=', $register_phone);
        }

        if ($user_account) {
            $query = $query->where('mbt_id', '=', $user_account);
        }
        
        if ($sub_com) {
            $query = $query->where('sub_company_list.sub_com_id', '=', $sub_com);
        }
       
        if ($account_status == 0){
            $query = $query->where('users.user_status', '=', $account_status);
        }elseif ($account_status == 1){
            $query = $query->where('users.user_status', '=', $account_status);
        }
        
        if ($reg_date_str) {
            $query = $query->whereDate('users.created_at', '>=', $reg_date_str_new);
        }
        
        if ($reg_date_end) { 
            $query = $query->whereDate('users.created_at', '<=', $reg_date_end_new);
        }
        
        if ($bind_date_str) {
            $query = $query->whereDate('bind_history.bind_date', '>=', $bind_date_str_new);
        }
        
        if ($bind_date_end) { 
            $query = $query->whereDate('bind_history.bind_date', '<=', $bind_date_end_new);
        }
        
        $data['payment'] = $query->paginate(10);
        return view('admin.user.search_bind_user',$data);
        
    }
    
    public function user_details($ln,$id)
    {
        $data['bind_user'] = DB::table('users')->where('id',$id)->first();
        $data['mbt_bind_user'] = null; // Initialize to prevent undefined variable error
        $student = ''; // Initialize student variable
        
        if (!$data['bind_user']) {
            return redirect()->back()->with('error', 'User not found');
        }
        
        $mbt_bind = DB::table('mbt_bind_user')->where('er_id',$data['bind_user']->bind_user_id)->first();
        $data['mbt_bind_user'] = $mbt_bind; // Always set this variable
        
        if(empty($mbt_bind))
        {
            return view('admin.user.details',$data, compact('student'));
        }
        else
        {
            $mbt_id=  MbtBindUser::where('er_id',$id)->first();
            $access_token = $this->gettoken(); //get_token 
    	    $userdata['username'] = "access_token=".$access_token."&user_name={$mbt_bind->user_name}";
    	    $url          = "api/v1/package/users-packages?".$userdata['username']; 
            $output       = $this->GetFunction($url);
            $decode       = json_decode($output,true);
            $d            = $decode['data'];
    	    $nirbhay      = array_values($d);
    	    $manish       =$nirbhay[0];
    	    $billing_names=$manish['billing_name'];
    	    $student=$billing_names;
    	    $data['names']=$id;
    	    
            $data['mbt_bind_user'] = DB::table('mbt_bind_user')->where('er_id',$data['bind_user']->bind_user_id)->first();
            return view('admin.user.details',$data,compact('student'));
        }
    }
    	public function GetFunction($url)
	{
	    //dd($url);
	    $mainUrl = $this->base_url.$url;
	    $headerArray =array("Content-type:application/json","Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $mainUrl);
		curl_setopt($ch, CURLOPT_POST, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
	    $output = curl_exec($ch);
		if ($output === false)
		{
			echo 'Curl error: ' . curl_error($ch);
		}
		else
		{
            //echo "<pre>";
            //echo PHP_EOL;  
            //echo "\n";
			//echo $output;
		}
		curl_close($ch);
		return $output;	
		
	}
	
    public function gettoken()
    {
        $url = $this->base_url."api/v1/auth/get-access-token"; 
        $headerArray =array("Content-type:application/json","Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15*60);
        $output = curl_exec($ch);
		if ($output === false)
		{
			echo 'Curl error: ' . curl_error($ch);
		}
		else
		{
		    echo PHP_EOL;  
			echo "\n";
			$decode_token = json_decode($output,true);
			$token = $decode_token['data']['access_token'];
			return  $token;
		};
		curl_close($ch);
	 }
	 
    public function bind_user_details($ln,$id)
    {
       
        $mbt_id=  MbtBindUser::where('er_id',$id)->first();
        $access_token = $this->gettoken(); //get_token  
	    $userdata['username']       = "access_token={$access_token}&user_name={$mbt_id->user_name}";
	      
	     
	   
	    $url          = "api/v1/package/users-packages?".$userdata['username']; 
	    $output       = $this->GetFunction($url);
	    
	    $decode       = json_decode($output,true);
	    $d            = $decode['data'];
	    $nirbhay      = array_values($d);
	    $manish       =$nirbhay[0];
	    $billing_names=$manish['billing_name'];
	
	    $student=$billing_names;
	  
	    $data['names']=$id;
	 
        $data['bind_user'] = MbtBindUser::where('er_id',$id)->first();
        return view('admin.user.bind_user_details',$data,compact('student'));
        
    }
    
    
     public function payment_detail_query($ln,$id)
    {

        $mbt_id=  MbtBindUser::where('er_id',$id)->first();
        $access_token = $this->gettoken(); //get_token  
	    $userdata['username']         = "access_token={$access_token}&user_name={$mbt_id->user_name}";
	    $url          = "api/v1/package/users-packages?".$userdata['username']; 
	    $output       = $this->GetFunction($url);
	    $decode       = json_decode($output,true);
	    $d            = $decode['data'];
	    $nirbhay      = array_values($d);
	    $manish       =$nirbhay[0];
	    $billing_names=$manish['billing_name'];
	
	    $student=$billing_names;
	  
	    $data['names']=$id;
	 
        $data['bind_user'] = MbtBindUser::where('er_id',$id)->first();
         return view('admin.user.bind_fault_details');
    }
    
    public function user_update()
    {
        $userphone   = $_GET['userphone']??'0';
        $username   = $_GET['username']??'0';
        $acc_status   = $_GET['acc_status']??'0';
        
        $pay_date_from = $_GET['reg_date_str']??'0';
        $pay_date_end   = $_GET['reg_date_end']??'0';
        
        $date_from_new = date('Y-m-d', strtotime($pay_date_from));
        $date_to_new   = date('Y-m-d', strtotime($pay_date_end));
        
        $query = User::query();
        
        if ($userphone) {
            $query = $query->where('phone', '=', $userphone);
        }
        if ($username) {
            $query = $query->where('name', '=', $username);
        }
        
        if ($acc_status == 0){
            $query = $query->where('user_status', '=', $acc_status);
        }elseif ($acc_status == 1){
            $query = $query->where('user_status', '=', $acc_status);
        }
        
        if ($pay_date_from) {
            $query = $query->whereDate('created_at', '>=', $date_from_new);
        }
        if ($pay_date_end) { 
            $query = $query->whereDate('created_at', '<=', $date_to_new);
        }
        
        $data['bind_user']  = $query->paginate(10);
                            
        $data['sub_com']   = SubCompany::all();
        return view('admin.user.update',$data);
    }
    
    public function update_users_query(Request $request)
    {
        $query = User::where('id',$request->query_id)->update([
        'name'        => $request->username,
        'sub_company'     => $request->sub_company,
        'phone'           => $request->phone,
        'bind_date'       => $request->bind_date,
        'user_status'     => $request->user_status,
            ]);
        
        return redirect()->back()->with('message','User Update Successfully!');
       // dd($request->all());
    }
    public function user_disabled()
    {
        //Date Filter for Register Date
        $reg_date_str   = $_GET['reg_date_str']??'0';
        $reg_date_end   = $_GET['reg_date_end']??'0';
        //For payment date 
        if (($reg_date_str !=0)&& ($reg_date_end !=0))
        {
              $date_from_new = date('Y-m-d', strtotime($reg_date_str));
              $date_to_new   = date('Y-m-d', strtotime($reg_date_end));
        }else
        { 
              $date_from_new = date('Y-m-d', strtotime('-365 days', time()));
              $date_to_new   = date('Y-m-d', strtotime(date('Y-m-d')));   
        }
        //Date Filter for Bind Date
        $bind_date_str   = $_GET['bind_date_str']??'0';
        $bind_date_end   = $_GET['bind_date_end']??'0';
        if (($bind_date_str !=0)&& ($bind_date_end !=0))
        {
              $bind_date_str = date('Y-m-d', strtotime($bind_date_str));
              $bind_date_end   = date('Y-m-d', strtotime($bind_date_end));
        }else
        { 
              $bind_date_str = date('Y-m-d', strtotime('-365 days', time()));
              $bind_date_end   = date('Y-m-d', strtotime(date('Y-m-d')));   
        }
       
        $data['bind_user'] = User::where('user_status',1)->whereDate('created_at', '>=', $date_from_new)
                            ->whereDate('created_at', '<=', $date_to_new)
                            ->whereDate('bind_date', '>=', $bind_date_str)
                            ->whereDate('bind_date', '<=', $bind_date_end)->get();
                            
        $data['sub_com']   = SubCompany::all();
        return view('admin.user.disable',$data);
    } 
    public function disable_role(Request $request)
    {
       // dd($request->all());
        return view('admin.user.disable');
    } 
    public function role_manage()
    {
        $role =  DB::table('role')->where('created_by',Auth::user()->id)->get();
        return view('admin.role.index',compact('role'));
    } 
    
     public function delete($ln,$id )
    {
    
      $delete = DB::table('role')->where('id',$id)->delete();
      return redirect()->back()->with('message','User Role Delete Successfully!');
    }
    
    
    
    public function role_add()
    {
        $permissions = DB::table('permissions')->whereNull('deleted_at')->get();
        return view('admin.role.add_role',compact('permissions'));
    }
    public function update_role($ln,$role_id)
    {
        $permissions = DB::table('permissions')->get();
        $role = DB::table('role')->where('id',$role_id)->first();
        //dd($ln);
        $set_permission = DB::table('permission_role')->where('role_id', $role_id)->get('permission_id');
        $d = json_decode(json_encode($set_permission),true);
        $array = array_column($d, 'permission_id');
    
        return view('admin.role.update',compact('array','permissions','role'));
    }
    public function add_permission()
    {
        return view('admin.user.permission');
    }
    
    public function store_role(Request $request)
    {
        //dd($request->all());
         $rules = array(
                'role_name'            => 'required',
                'permission'           => 'required',
               // 'password'             => 'required|confirmed'
            );

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
        {
           //dd($validator);
            return redirect()->back()->withErrors($validator)->withInput();
        }else
        {  
            //permission_role
            $create = DB::table('role')->insertGetId([
                'name'           => $request->role_name,
                'created_by'     => Auth::user()->id,
                ]);
                
            // Add Permission Table
            foreach($request->permission as $value)
            {
                $permission = DB::table('permission_role')->insert([
                 'role_id'       => $create,
                 'permission_id' => $value,
                ]);
            }
            
                    return redirect('en/admin/user-role-manage')->with('message', 'Role And Permission Set Successfully!');


           // return redirect()->back()->with('message','Role And Permission Set Successfully!');
        }
    }
    
    public function edit_role(Request $request)
    {
    // dd($request->all());
      $rules = array(
                'role_name'            => 'required',
                'permission'           => 'required',
                //'password'             => 'required|confirmed'
            );

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
        {
            return back()->withErrors($validator)->withInput();
        }else
        {  
            $create = DB::table('role')->where('id',$request->role_id)->update([
                'name'           => $request->role_name,
               //   'role_password'  => Hash::make($request->password),
                //  'plain_password' => $request->password,
                ]);
          //  $password_update = Admin::where('role_id',$request->role_id)->update(['password'=>Hash::make($request->password)]);
            $delete = DB::table('permission_role')->where('role_id',$request->role_id)->delete();
            // Add Permission Table
            foreach($request->permission as $value)
            {
                $permission = DB::table('permission_role')->insert([
                 'role_id'       => $request->role_id,
                 'permission_id' => $value,
                ]);
            }
        
        return redirect('en/admin/user-role-manage')->with('message', 'Role And Permission update Successfully!');

            //return redirect()->back()->with('message','');
        }
      
    }
    
    
    //Mesaage
    public function message(Request $request)
    {   
        $data['user'] = DB::table('users')->where('id', '!=', '1')->orderBy('last_msg_date', 'DESC')->limit(100)->get();
         
        return view('admin.message.chat',$data);
    }
    
    public function fetch_user(Request $request)
    {    
        die('No users found!');
        $user_id = $_GET['user_id'];
        $users = User::where('id', '!=', '1')->where('name', 'like', '%' . $user_id . '%')->orderBy('last_msg_date', 'DESC')->get();
        
        $decode = json_decode(json_encode($users),true);
    
        foreach($decode as $user){
            $bind_user = MbtBindUser::where('er_id',$user["bind_user_id"])->first();
            if(!empty($bind_user['user_name'])){
                $username = $bind_user['user_name'];
            }else{
                $username = 'N/A';
            }
            
            $abcd= DB::table('chat')->where('read_status',0)->where('sender_userid',$user["id"])->count();
            if($abcd >= 1){
                $new_msg = '<span id="part'.$user["id"].'"><small style="margin-left: -4px;">'.$abcd.'</small></span>';
            }else{
                $new_msg = '';
            }
			            
            $newusers[] = '<li id="'.$user["id"].'" class="contact myTable '.$user["id"].'" data-touserid="'.$user["id"].'" data-tousername="'.$user["name"].'">
                    <div class="wrap">'.$new_msg.'
    				    <img src="https://test.mbt.com.mm/assets/admin/img/avatar.png" alt="" style="width: 30px; float: left; margin-right: 10px;">
    				    <div class="meta">
        					<p class="name">'.$user["name"].'</p>
        					<p class="preview"><span id="isTyping_1" class="isTyping" style="padding-left: 40px;">'. $username .'</span></p>
    				    </div>
    				</div>
    			</li>';
        }
        
        return $newusers;
    }
    
    public function get_user_details(Request $request)
    {
        $user = User::where('id',$request->user_id)->first();
        echo json_encode($user);
        //dd($request->all());
    }
    
    public function marketting_information()
    {
        $reg_date_str   = $_GET['start_date']??'0';
        $reg_date_end   = $_GET['end_date']??'0';
        //For payment date 
        if (($reg_date_str !=0)&& ($reg_date_end !=0))
        {
              $date_from = date('Y-m-d', strtotime($reg_date_str));
              $date_to   = date('Y-m-d', strtotime($reg_date_end));
        }else
        { 
              $date_from = date('Y-m-d', strtotime('-365 days', time()));
              $date_to   = date('Y-m-d', strtotime(date('Y-m-d')));   
        }
        $data['user'] = MbtBindUser::pluck('user_name')->toArray();
        
       
      //  $data['Notification'] = Notification::distinct()->where('is_multi','=','1')->where('marketing_information_status',100)->whereDate('created_at', '<=', $date_to)->whereDate('created_at', '>=', $date_from)->orderBy('created_at','desc')->pluck('created_at');
        $data['notifications'] = Notification::groupBy('created_at')->where('is_multi','=','1')->where('marketing_information_status',100)->whereDate('created_at', '<=', $date_to)->whereDate('created_at', '>=', $date_from)->orderBy('created_at','desc')->get('created_at');
        $data['all_notifications'] = Notification::where('is_multi','=','1')->select('notification.publish_info')->groupBy('publish_info')->paginate(10);
        $data['users'] = User::paginate(50);
        return view('admin.marketting.send_bulk_notification',$data);
    }
    
    public function user_notification()
    {
          //Date Filter for Register Date
        $reg_date_str   = $_GET['start_date']??'0';
        $reg_date_end   = $_GET['end_date']??'0';
        //For payment date 
        if (($reg_date_str !=0)&& ($reg_date_end !=0))
        {
              $date_from = date('Y-m-d', strtotime($reg_date_str));
              $date_to   = date('Y-m-d', strtotime($reg_date_end));
        }else
        { 
              $date_from = date('Y-m-d', strtotime('-365 days', time()));
              $date_to   = date('Y-m-d', strtotime(date('Y-m-d')));   
        }
        $data['user'] = MbtBindUser::pluck('user_name')->toArray();
        $data['allusers']=DB::table('users')->get();
        $data['all_notifications'] = Notification::where('is_multi','=','0')->select('notification.publish_info')->groupBy('publish_info')->paginate(10);
        return view('admin.marketting.single_notification',$data);
    }
    
   public function StoreUserNotification(Request $request)
   {  
       
        $devices = DB::table('user_devices')->get();
        foreach($devices as $device){
            foreach($request->account_id as $value){
                
                $check_device  = User::where(['phone'=>$value,'bind_id'=>1])->first()->id??'na';
                $bind_id = User::where(['phone'=>$value,'bind_id'=>1])->first()->bind_user_id??'na';
                
                if($check_device == $device->user_id){
                   $insert = new Notification();
                   $insert->account_id   = MbtBindUser::find($bind_id)->user_name??'';
                   $insert->publish_info = $request->publish_information;
                   $insert->install_user_id = $device->device_id??'';
                   $insert->is_multi     = 0;
                   $insert->save();
                   
                   Helper::SendFirebasePush($device->fcm_token,$request->publish_information);
                }
            }
        }
    
          
       $notification = array(
            'messege' => 'Send Notification Successfully',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
   }
   
    public function StoreMultiUserNotification(Request $request)
    {   
        $users = $request->users;
        $devices = DB::table('user_devices')->get();
        
        foreach($users as $user){
            foreach($devices as $device){
                if($user == $device->user_id){
                    $fcm= $device->fcm_token;
                    Helper::SendFirebasePush($fcm,$request->publish_information);
                    
                    $check_users = DB::table('users')->where('id', $device->user_id)->first();
            
                    if(!empty($check_users->bind_user_id)){
                        $account_id = MbtBindUser::find($check_users->bind_user_id)->user_name;
                    }else{
                        $account_id = '';
                    }
                   
                    DB::table('notification')->insert([
                       'account_id' => $account_id, 
                       'publish_info' =>  $request->publish_information,
                       'install_user_id'=> $device->device_id??''
                    ]);
                }
            }
        }

        $notification = array(
            'messege' => ' Successfully Send Notification To All Customer',
            'alert' => 'success'
        );
        return redirect()->back()->with('notification', $notification);
    }
    
    public function fetch_all_user(Request $request)
    {    
        $user_id = $_GET['user_id'];
        $users = User::where('id', '!=', '1')->where('name', 'like', '%' . $user_id . '%')->orderBy('last_msg_date', 'DESC')->get();
        
        $decode = json_decode(json_encode($users),true);
    
        foreach($decode as $user){
            $newusers[] = '<tr>
                            <td>'.$user["id"].'</td>
                            <td>
                                <input type="checkbox" id="checkItem" name="users[]" value="'.$user["id"].'">
                            </td>
                            <td>'.$user["name"].'</td>    
                            <td>'.$user["phone"].'</td>
                        </tr>';
        }
        
        return $newusers;
    }
   
   
   
   public function get_user_chat(Request $request)    
   {
        $user1 = Auth()->guard('admin')->user()->id;
        $user2 = $request->user_id;
            
        //$sqlQuery = DB::table('chat')->where(['reciever_userid'=>$user1,'sender_userid'=>$user2])->orWhere(['reciever_userid'=>$user2,'sender_userid'=>$user1])->orderBy('timestamp','ASC')->get();
        
        $sqlQuery = DB::select("select * from chat where `reciever_userid` = $user2 or `sender_userid` = $user2 order by timestamp asc");
      
      	
      	DB::table('chat') ->where('sender_userid', $request->user_id)->update(['read_status' => 1]); 
      	
      	$decode = json_decode(json_encode($sqlQuery),true);
      	$conversation = '<ul class="message-body">'; 
		foreach($decode as $chat){
		    
		    $img = Auth()->guard('admin')->user()->image;
		    $userimg = Auth()->guard('admin')->user()->image;
			$user_name = '';
			if($chat["sender_userid"] == Auth()->guard('admin')->user()->id) {
				$conversation .= '<li class="replies">';
				$conversation .= '<img width="22px" height="22px" src="https://telco.mbt.com.mm/assets/front/img/'.$img.'" alt="" />';
			} else {
				$conversation .= '<li class="send">';
				$conversation .= '<img width="22px" height="22px" src="https://telco.mbt.com.mm/assets/admin/img/avatar.png" alt="" />';
			}			
			$conversation .= '<p>'.$chat["message"].'<br>'.$chat["timestamp"].'</p>';			
			$conversation .= '</li>';
		}
		$conversation .= '</ul>';
		return $conversation;
   }
   
    public function insert_user_chat(Request $request)
    { 
        date_default_timezone_set('Asia/yangon');
        $ldate=date('Y-m-d h:i:s');
        
        $user1 = Auth()->guard('admin')->user()->id;
        $user2 = $request->user_id;
          
        $sqlQuery = DB::select("select * from chat where `reciever_userid` = $user2 or `sender_userid` = $user2 order by timestamp asc");
        
        DB::table('users') ->where('id', $request->user_id)->update(['last_msg_date' => $ldate]); 
        
        $insert = DB::table('chat')->insert(['sender_userid'=>$request->from_user_id,'reciever_userid'=>$request->user_id,'message'=>$request->message,'status'=>0]);
        if($insert)
        {     
            //DB::table('users')->where('id', 7)->update(array('last_messsage' => ok));  
            $user = User::find($request->user_id);
            Helper::SendFirebasePush($user->device_id,$request->message);
            
          	$sqlQuery = DB::select("select * from chat where `reciever_userid` = $user2 or `sender_userid` = $user2 order by timestamp asc");
          	
          	$decode = json_decode(json_encode($sqlQuery),true);
          	$conversation = '<ul>'; 
    		foreach($decode as $chat){
    		    $img = Auth()->guard('admin')->user()->image;
    		    $userimg = Auth()->guard('admin')->user()->image;
    			$user_name = '';
    			if($chat["sender_userid"] == $request->from_user_id) {
    				$conversation .= '<li class="replies">';
    				$conversation .= '<img width="22px" height="22px" src="https://telco.mbt.com.mm/assets/front/img/'.$img.'" alt="" />';
    			} else {
    				$conversation .= '<li class="send">';
    				$conversation .= '<img width="22px" height="22px" src="https://telco.mbt.com.mm/assets/admin/img/avatar.png" alt="" />';
    			}			
    			$conversation .= '<p>'.$chat["message"].'<br>'.$chat["timestamp"].'</p>';	
    			$conversation .= '</li>';
    		}		
    		$conversation .= '</ul>';
    		//dd($conversation);
    		return $conversation;
       }else
       {
           
       }
   }
   
   public function notification(request $request)
   {  
      // echo $abcd=$_GET['yearname'];
       //echo "okay";
    //   $usernoti = DB::table('users')
    //               ->where('notification_userid',0)->get();
    //               print_r($usernoti);
    //               die();

    //   return view('admin.partials.top-navbar',compact('usernoti'));
      
   }
    
   
}  

