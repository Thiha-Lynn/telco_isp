<?php

namespace App\Http\Controllers\Admin;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use PDF;
use App\Models\MbtBindUser;
use App\Models\SubCompany;
use DB;
use App\Models\PaymentGatewey;
use App\Models\PaymentNew;
use App\Models\UserQuery;
use URL;
use App\Models\FaultReportQuery;
use App\Models\Binduser;
use App\Models\Setting;

class ExportController extends Controller
{
    public function export_users_excel()
    {
        header("Content-Type: application/xls");    
        header("Content-Disposition: attachment; filename=users.xls");  
        header("Pragma: no-cache"); 
        header("Expires: 0");
        
        $users = User::get();
        
        echo '<table border="1">';
        echo '<tr><th>#</th><th>Name</th><th>Register phone</th><th>Login Password</th><th>Register date</th><th>Account Status</th></tr>';
        foreach ($users as $k=>$user){
           
            if($user->user_status == 0)
            {
                $status = "<b class='badge badge-warning'>Normal</b>"; 
            }else
            {
                $status = "<b class='badge badge-danger'>Disabled</b>";
            }
            
            // SECURITY: Show masked password instead of plaintext
            $maskedPassword = $user->new_pass ? '••••••••' : 'N/A';
                                                    
            echo "<tr><td>".++$k."</td><td>".$user->name."</td><td>".$user->phone."</td><td>".$maskedPassword."</td><td>".date('Y-m-d', strtotime($user->created_at))."</td><td>".$status."</td></tr>";
        }
        echo '</table>';
        exit();
    }
    
    public function export_users_pdf()
    {
        // Export ALL users (removed limit(10))
        $data = User::get();
        
        $pdf = PDF::loadView('admin.user.userpdf', compact('data'))->setPaper('A4', 'landscape');
        return $pdf->download('users.pdf');
    }
       public function export_fault_excel()
    {
        
        $date=Date('Y-m-d H:i:s');
        $parsedUrl = parse_url(URL::previous()); // $parsedUrl['post']; // www.example.com
        // $parsedUrl['path']; // /posts
        $value=$parsedUrl['query']??''; // param=val&param2=val
        $mbt_account_id=0;
        $sub_company=0;
        $date_from_new=0;
        $date_to_new=0;
        $action=0;
        if($value  ){
        parse_str($parsedUrl['query'], $output);
        $mbt_account_id=$output['mbt_account_id'];
        $sub_company=$output['sub_company'];
        $date_from_new=$output['report_start_date'];
        $date_to_new=$output['report_end_date'];
        $action=$output['action'];
        }
        header("Content-Type: application/xls");    
        header("Content-Disposition: attachment; filename=".$date."-User Query Payment Record.xls");  
        header("Pragma: no-cache"); 
        header("Expires: 0");
        
           $fault = FaultReportQuery::query();
         
         
           if($mbt_account_id){
                $fault = $fault->where('mbt_id', '=', $mbt_account_id);
            }
            if($sub_company) {
                $fault = $fault->where('sub_com_id', '=', $sub_company);
            }
            if($action) {
                $fault = $fault->where('fault_status', '=', $action);
            }
            if($date_from_new) {
                $fault = $fault->whereDate('created_at', '>=', $date_from_new);
            }
            if($date_to_new) { 
                $fault = $fault->whereDate('created_at', '<=', $date_to_new);
            }
        $fault=$fault->get();
        $sub_com   = SubCompany::all();
        $user   = User::all();
      
        echo '<table border="1">';
        echo '<tr><th>#</th><th>Full name</th><th>MBT account id</th><th>Sub company</th><th>Report date</th><th>Phone</th><th>Address</th><th>Fault Details</th><th>Status</th></tr>';
        foreach ($fault as $k=>$value){
           
             if($value->fault_status == 0){
                    $status="<b class='badge badge-success'>Accept</b>";
       
            }elseif($value->fault_status == 1){
             
                      $status="<b class='badge badge-warning'>Processing</b>";
            }elseif($value->fault_status == 3){
                   
                        $status="<b class='badge badge-danger'>Not Accept</b>";
            }else{
                  
                       $status="<b class='badge badge-primary'>Complete</b>";
            }
                                      
            
                 $name=User::find($value->user_id)->name??'';
                 $bind_user = User::find($value->user_id)->bind_user_id??'';
                 $account=MbtBindUser::find($bind_user)->user_name??'';
                 $subcompany=SubCompany::find($value->sub_com_id)->company_name;
            
                                                    
            echo "<tr><td>".++$k."</td><td>".$name."</td><td>".$account."</td><td>".$subcompany."</td><td>".$value->report_date."</td><td>".$value->fault_number."</td><td>".$value->fault_address."</td><td>".substr($value->fault_details,0,50)."</td><td>".$status."</td></tr>";
        }
        echo '</table>';
        exit();
    }
    
     public function export_fault_pdf()
    {
        $date=Date('Y-m-d H:i:s');
        $fault = DB::table('fault_report_query')->limit(10)->get();
        $sub_com   = SubCompany::all();
        $user   = User::all();
        
        $pdf = PDF::loadView('admin.user.faultpdf', compact('fault'))->setPaper('A4', 'landscape');
        return $pdf->download($date.'User Query Payment Record.pdf');
    }
    
    
    
    public function export_payment_excel()
    {
        try{
            $date=Date('Y-m-d H:i:s');
            $parsedUrl = parse_url(URL::previous()); // $parsedUrl['post']; // www.example.com
            // $parsedUrl['path']; // /posts
            $value=$parsedUrl['query']??''; // param=val&param2=val
            $user_account=0;
            $sub_com_id=0;
            $Pay_method=0;
            $pay_date_froms=0;
            $pay_date_ends=0;
            $expiery_date_from=0;
            $result_status=0;
            $expiery_date_end=0;
            if($value  ){
                parse_str($parsedUrl['query'], $output);
                $user_account=$output['user_account'];
                $sub_com_id=$output['sub_com_id'];
                $Pay_method=$output['Pay_method'];
                $result_status=$output['result_status'];
                $pay_date_from=$output['pay_id'];
                $pay_date_end=$output['pay_result'];
                $expiery_date_from=$output['expiery_date_from'];
                $expiery_date_end=$output['expiery_date_end'];
          
                $pay_date_froms = (int)$pay_date_from;
                $pay_date_ends   = (int)$pay_date_end;
                
                $expirey_from_new = date('Y-m-d', strtotime($expiery_date_from));
                $expirey_to_new   = date('Y-m-d', strtotime($expiery_date_end));
            }
            header("Content-Type: application/xls");    
            header("Content-Disposition: attachment; filename=".$date."-User Query Payment Record.xls");  
            header("Pragma: no-cache"); 
            header("Expires: 0");
        
             $query = PaymentNew::query();
            //  $query->where('admin_status',1);
            //  $query->orWhere('admin_status',2);
            //  $query ->orderBy('created_at', 'DESC');
            

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
                $query = $query->whereDate('trans_date', '<=', $expirey_to_new);
            }
            
             $payment = $query->get();
             
            $settings = Setting::where('id', '1')->first();
            $base_url = $settings->ip_address;
            
            $url = $base_url."api/v1/auth/get-access-token"; 
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 0); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_HTTPHEADER,array("Content-type:application/json","Accept:application/json"));
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
                $access_token = $decode_token['data']['access_token'];
            };
            curl_close($ch);
          
        echo '<table border="1">';
        echo '<tr><th>#</th><th>Register phone</th><th>User Account ID</th><th>Sub company</th><th>Pay date</th><th>Pay amoun</th><th>Invoice-number</th><th>Order-number</th><th>Begin-date</th><th>Expire-time</th><th>Pay-Method</th><th>Pay Result</th></tr>';
        foreach ($payment as $k=>$value){
            if($value->admin_status == 1){
                $status="<b class='badge badge-success'>Success</b>";
            }elseif($value->admin_status == 2){
                $status="<b class='badge badge-warning'>Fail</b>";
            }elseif($value->admin_status == 3){
                $status="<b class='badge badge-danger'>Cancel</b>";
            }else{
                $status="<b class='badge badge-primary'>Pending</b>";
            }

            $payment_name=PaymentGatewey::whereIn('id', [8,9,10,11,12])->find($value->payment_method)->title??'NA'  ;             
            $subcompany=SubCompany::find($value->sub_com_id)->company_name??'';
            
            if($value->expire_date == 'NaN-NaN-NaN 23:00:00'){
                $user_name    = $value->payment_user_name;
                $data         = "access_token={$access_token}&user_name={$user_name}";
                $url_new      = "api/v1/package/users-packages?".$data; 
                
                $mainUrl = $base_url.$url_new;
              
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $mainUrl);
                curl_setopt($ch, CURLOPT_POST, 0); 
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch,CURLOPT_HTTPHEADER,array("Content-type:application/json","Accept:application/json"));
                curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
                $output = curl_exec($ch);
                curl_close($ch);
                $decode       = json_decode($output,true);
                if(!empty($decode['data'][0]['package'])){
                    $total_pack = count($decode['data'][0]['package']);
                    $exp_num = $total_pack-1;
                    $exp_time = $decode['data'][0]['package'][$exp_num]['valid_day'];
                }else{
                    $exp_time = '0';
                }
                
                $exp_date = date('Y-m-d h:i:s', strtotime($value->begin_date. ' + '.$exp_time.' days'));
            }else{
                $exp_date = $value->expire_date??'';
            }
                                                    
            echo "<tr><td>".++$k."</td><td>".$value->phone."</td><td>".$value->payment_user_name."</td><td>".$subcompany."</td><td>".$value->trans_date."</td><td>".$value->total_amt."</td><td>".$value->invoice_no."</td><td>".$value->order_id."</td><td>".$value->begin_date."</td><td>".$exp_date."</td><td>".$payment_name."</td><td>".$status."</td></tr>";
        }
        echo '</table>';
        exit();
        }catch(\Exception $e){
            dd($e->getMessage().$e->getLine());
        }
    }
    
     public function export_binduser_excel()
    {
        
        try{
        $date=Date('Y-m-d H:i:s');
        $parsedUrl = parse_url(URL::previous()); // $parsedUrl['post']; // www.example.com
        // $parsedUrl['path']; // /posts
        $value=$parsedUrl['query']??''; // param=val&param2=val
        $sub_com=0;
        $user_account=0;
        $account_status=0;
        $register_phone=0;
        $reg_date_str=0;
        $reg_date_end=0;
        $bind_date_str=0;
        $bind_date_end=0;
        if($value){
        parse_str($parsedUrl['query'], $output);
        
        $sub_com=$output['sub_com'];
        $sub_company=$output['user_account'];
        $account_status=$output['account_status'];
        $reg_date_str=$output['reg_date_str'];
        $reg_date_end=$output['reg_date_end'];
        $bind_date_str=$output['bind_date_str'];
        $bind_date_end=$output['bind_date_end'];
        $register_phone=$output['register_phone'];
   
        $reg_date_str_new   = date('Y-m-d', strtotime($reg_date_str));
        $reg_date_end_new   = date('Y-m-d', strtotime($reg_date_end));
        $bind_date_str_new = date('Y-m-d', strtotime($bind_date_str));
        $bind_date_end_new   = date('Y-m-d', strtotime($bind_date_end));
        
        }
        header("Content-Type: application/xls");    
        header("Content-Disposition: attachment; filename=".$date."-bind User  Record.xls");  
        header("Pragma: no-cache"); 
        header("Expires: 0");
    
        $query = Binduser::query();
        $query = $query->select('users.*', 'bind_history.*')->join('users', 'users.id', '=', 'bind_history.user_id');
        $query = $query->select('sub_company_list.*', 'bind_history.*')->join('sub_company_list', 'sub_company_list.sub_com_id', '=', 'bind_history.sub_company');
        if($value){
       
        
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
        }
        
        $fault=$query->get();
// dd($fault);
        echo '<table border="1">';
        echo '<tr><th>#</th><th>MBT Account ID</th><th>Real Name</th><th>Register phone</th><th>Register date</th><th>Bind Date</th><th>Unbind Date</th><th>Sub Company</th><th>User Status</th></tr>';
        foreach ($fault as $k=>$value){
           
            $user = DB::table('users')->where('id',$value->user_id)->first();
                    if(!empty($user) && $user->user_status == 0)
                    {
                        $status = "<b class='badge badge-warning'>Normal</b>";
                    }else
                    {
                        $status = "<b class='badge badge-danger'>Disabled</b>";
                    }
                                      
                  $mbt_bind = DB::table('mbt_bind_user')->where('user_name',$value->mbt_id)->first()??''; 
                  $phone=User::Where('id',$value->user_id)->first()->phone??'';
                  $register =User::Where('id',$value->user_id)->first()->created_at??'';
                  if(!empty($mbt_bind)){
                     $subcompany=SubCompany::find($mbt_bind->Sub_company)->company_name??'';
                  }else{
                      $subcompany='';
                  }
                  if(!empty($mbt_bind)){
                      $user_real_name=$mbt_bind->user_real_name;
                  }else{
                      $user_real_name='';
                  }
                  
                                                    
            echo "<tr><td>".++$k."</td><td>".$value->mbt_id."</td><td>".$user_real_name."</td><td>".$phone."</td><td>".$register."</td><td>".$value->bind_date."</td><td>".$value->unbind_date."</td><td>".$subcompany."</td><td>".$status."</td></tr>";
        }
        echo '</table>';
        exit();
        }catch(\Exception $e){
            dd($e->getMessage().$e->getLine());
        }
    }
    
    public function export_install_excel()
    {
        
        $date=Date('Y-m-d H:i:s');
        $parsedUrl = parse_url(URL::previous()); // $parsedUrl['post']; // www.example.com
        // $parsedUrl['path']; // /posts
        $value=$parsedUrl['query']??''; // param=val&param2=val
        $mbt_account_id=0;
        $status=0;
        $pay_date_from=0;
        $pay_date_end=0;
        if($value ){
        parse_str($parsedUrl['query'], $output);
        $mbt_account_id=$output['Register_phone'];
        $pay_date_from=$output['start_date'];
        $pay_date_end=$output['end_date'];
          
        $date_from_new = date('Y-m-d', strtotime($pay_date_from));
        $date_to_new   = date('Y-m-d', strtotime($pay_date_end));
        
        }
        header("Content-Type: application/xls");    
        header("Content-Disposition: attachment; filename=".$date."-Install Query  Record.xls");  
        header("Pragma: no-cache"); 
        header("Expires: 0");
      
      
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
            
             $user_query  = $query->get();
      
      
        echo '<table border="1">';
        echo '<tr><th>#</th><th>Username</th><th>Report Date</th><th>Phone</th><th>Apply Address</th><th>Action</th></tr>';
        foreach ($user_query as $k=>$value){
           
             if($value->query_status == 1){
                    $status="<b class='badge badge-success'>Accept</b>";
       
            }elseif($value->query_status == 2){
             
                      $status="<b class='badge badge-warning'>No Accept</b>";
            }elseif($value->query_status == 3){
                   
                        $status="<b class='badge badge-danger'>Processing</b>";
            }else{
                  
                       $status="<b class='badge badge-primary'>Complete</b>";
            }
                                      
            
                 $name=User::find($value->user_id)->name??'';
                                                    
            echo "<tr><td>".++$k."</td><td>".$name."</td><td>".$value->apply_date_start."</td><td>".$value->user_number."</td><td>".$value->address."</td><td>".$status."</td></tr>";
        }
        echo '</table>';
        exit();
    }
    
    
}