<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Auth;
use Mail;
use App\Models\Service;
use App\Models\About;
use App\Models\Branch;
use App\Models\Package;
use App\Models\Product;
use App\Models\Scategory;
use App\Models\Team;
use App\Models\MbtBindUser;
use App\Models\PaymentQuery;
use App\Models\Testimonial;
use App\Models\PaymentGatewey;
use App\Models\FaultReportQuery;
use App\Models\UserQuery;
use App\Models\PermissionModel;
use App\Models\SubCompany;
use App\Models\Notification;
use App\Models\ErrorCode;
use DB;
use App\Models\PaymentNew;
use App\Models\UserDevice;
use App\Models\CborderDetail;
use App\Models\PendingPayment;
use App\Models\AyaCallback;
use App\Models\BankSetting;
use App\Models\Setting;
use App\Models\ExtraMonth;
use App\Models\Promotion;
use Illuminate\Support\Facades\Cache;
use DateTime;
use App\Models\WaveCallback;
use App\Models\MaintenanceSetting;
use App\Models\PaymentProcess;

class MbtController extends Controller
{
    const INIT_VECTOR_LENGTH = 16;
    const CIPHER = 'AES-128-CBC';
    
    public $successStatus      = 200;
    public $unauthorisedStatus = 400;
    
    public function __construct()
    {
        $settings = Setting::where('id', '1')->first();
        $this->base_url = $settings->ip_address;
        $this->number_days = $settings->number_days;
        $this->new_user_days = $settings->new_user_days;
        $this->new_user_days1 = $settings->new_user_days1;
        $this->discount = $settings->discount;
        $this->commercial_tax = $settings->commercial_tax;
        
        $details = BankSetting::where('id', '1')->first();
        // CB Pay
        $this->api_url          = $details->api_url;
        $this->auth_token       = $details->auth_token;
        $this->ecommerce_id     = $details->ecommerce_id;
        $this->sub_mer_id       = $details->sub_mer_id;
        $this->mer_id           = $details->mer_id;
        $this->transaction_type = $details->transaction_type;
        $this->notifyurl        = $details->notifyurl;
        $this->cb_redirect      = $details->cb_redirect;
        
        // KBZ Pay
        $this->kbz_api_url      = $details->kbz_api_url;
        $this->kbz_m_code       = $details->kbz_m_code;
        $this->kbz_appid        = $details->kbz_appid;
        $this->kbz_key          = $details->kbz_key;
        $this->kbz_trade_type   = $details->kbz_trade_type;
        $this->kbz_notifyurl    = $details->kbz_notifyurl;
        $this->kbz_version      = $details->kbz_version;
        $this->kbz_redirecct    = $details->kbz_redirecct;
        
        // AYA Pay
        $this->aya_api_tokenurl    = $details->aya_api_tokenurl;
        $this->aya_consumer_key    = $details->aya_consumer_key;
        $this->aya_consumer_secret = $details->aya_consumer_secret;
        $this->aya_grant_type      = $details->aya_grant_type;
        $this->aya_api_baseurl     = $details->aya_api_baseurl;
        $this->aya_phone           = $details->aya_phone;
        $this->aya_password        = $details->aya_password;
        $this->aya_enc_key         = $details->aya_enc_key;
        
        // KBZ Direct Pay
        $this->direct_apiurl   = $details->direct_apiurl;
        $this->direct_mcode    = $details->direct_mcode;
        $this->direct_key      = $details->direct_key;
        
        // Wave Pay
        $this->wave_live_seconds   = $details->wave_live_seconds;
        $this->wave_merchnt_id     = $details->wave_merchnt_id;
        $this->wave_callback_url   = $details->wave_callback_url;
        $this->wave_secret_key     = $details->wave_secret_key;
        $this->wave_base_url       = $details->wave_base_url;
    }
    
    public function index()
    {
       
        echo  $this->successStatus;
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
        curl_close($ch);
        
        if ($output === false)
        {
          return null;
        }
        
        $decode_token = json_decode($output, true);
        if (isset($decode_token['data']['access_token'])) {
            return $decode_token['data']['access_token'];
        }
        
        return null;
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
    //   echo 'Curl error: ' . curl_error($ch);
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
  
    public function PostFunction($url,$dataobj)
    {
        $mainUrl = $this->base_url.$url;
        $data  = json_encode($dataobj,JSON_UNESCAPED_UNICODE); 
        $headerArray =array("Content-Type:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $mainUrl); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);  
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);  
        curl_setopt($curl, CURLOPT_POST, 1);  
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST,"POST");  
        curl_setopt($curl, CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15*60);                       
        $output = curl_exec($curl);
        $outobj = json_decode($output,true);
        if ($output === false)
        {
            echo "\n";
            echo 'Curl error: ' . curl_error($curl);
        }
        else
        {
            //echo "<pre>";
            //echo PHP_EOL;  // 
            //echo "\n";
            //echo $output;
        }
        curl_close($curl);
        return $output;
  }
  //Store User on MBT server
  public function StoreUser()
  {
      $access_token = $this->gettoken(); //get_token
      $dataobj["access_token"]    = $access_token;
      $dataobj["username"]        = request('user_name');
      $dataobj["user_real_name"]  = request('user_real_name');  
      $dataobj["user_password"]   = request('user_password');
      $dataobj["group_id"]        = request('group_id');
      $dataobj["products_id"]     = request('products_id');
      $url                        = "api/v1/users"; 
      $output                     = $this->PostFunction($url,$dataobj);
      print_r($output);
  }
  //Super Search
  public function UserSuperSearch()
  {
      $access_token    = $this->gettoken(); //get_token
      $access_token    = $access_token;
      $user_name       = request('user_name');
      $data            = "access_token={$access_token}&user_name={$user_name}&per-page=10";
        $url             = "api/v1/user/super-search?".$data; 
      $output          = $this->GetFunction($url);
      print_r($output);   
  }
  //View User from MBT server
  public function ViewUser()
  {
      $access_token    = $this->gettoken(); //get_token
      $access_token    = $access_token;
      $user_name       = request('user_name');
      $data            = "access_token={$access_token}&user_name={$user_name}";
        $url             = "api/v1/user/view?".$data; 
      $output          = $this->GetFunction($url);
      print_r($output);
  }
  
  //Get Paymemt Records
  public function paymentRecordsInsert($user_name)
  {
      $access_token = $this->gettoken(); //get_token (user update)
      $data         = "access_token={$access_token}&user_name={$user_name}";
      $url          = "api/v1/financial/payment-records?".$data; 
      $output       = $this->GetFunction($url);
      $decode       = json_decode($output,true);
      if(count($decode['data']) > 0)
      {
          foreach($decode['data'] as $value)
          {
              $check = PaymentQuery::where('invoice_no',$value['bill_number'])->first();
              if(empty($check))
              {
                
                  $SubComId = MbtBindUser::where('user_name',$user_name)->first(['sub_company','er_id']);
                  $payment  = new PaymentQuery();
                  $payment->sub_com_id        = $SubComId->sub_company;
                  $payment->user_id           = $SubComId->er_id;
                  $payment->payment_user_name = $value['user_name'];
                  $payment->trans_date        = date('Y-m-d H:i:s',$value['create_at']); 
                  $payment->total_amt         = $value['pay_num'];
                  $payment->order_id          = $value['order_no'];
                  $payment->invoice_no        = $value['bill_number'];
                  $payment->payment_method    = $value['pay_type_id'];
                  $payment->package_id        = $value['package_id']; 
                  $payment->product_id        = $value['product_id'];
                  $payment->save();
              }else
              {
                 // echo "alrady inserted!";
              }
          }
     }
  }
  
    public function paymentRecords(Request $request)
    {
        $access_token = $this->gettoken(); //get_token history
        $user_name    = request('username');
        $page         = request('page');
        $data         = "access_token={$access_token}&user_name={$user_name}&page={$page}";
        $url          = "api/v1/financial/payment-records?".$data; 
        $output       = $this->GetFunction($url);
        $decode       = json_decode($output,true);
        $page         = $decode['_meta'];
        $value        = $decode['data'];

        if(count($decode['data']) > '0')
        {  
            foreach($decode['data'] as $value)
            {
                $check = PaymentQuery::whereNotNull('bill_number')->first();
             
                if(empty($check))
                {
                    $SubComId = MbtBindUser::where('user_name',$user_name)->first(['sub_company','er_id']);
                    $payment  = new PaymentQuery();
                    $payment->sub_com_id        = $SubComId->sub_company;
                    $payment->user_id           = $SubComId->er_id;
                    $payment->payment_user_name = $value['user_name'];
                    $payment->trans_date        = date('Y-m-d H:i:s',$value['create_at']); 
                    $payment->total_amt         = $value['pay_num'];
                    $payment->order_id          = $value['order_no'];
                    $payment->invoice_no        = $value['bill_number'];
                    $payment->payment_method    = $value['pay_type_id'];
                    $payment->package_id        = $value['package_id']; 
                    $payment->product_id        = $value['product_id'];
                    $payment->bill_number       = $value['bill_number'];
                    $payment->save();
                  
                    $payment_remove = PaymentQuery::where('payment_user_name',$value['user_name'])->where('total_amt',$value['pay_num'])->whereNull('bill_number')->delete();
                }else{
                    $check_repeat = PaymentQuery::where('bill_number',$value['bill_number'])->first();
                    if(empty($check_repeat)){
                        $SubComId = MbtBindUser::where('user_name',$user_name)->first(['sub_company','er_id']);
                        $payment  = new PaymentQuery();
                        $payment->sub_com_id        = $SubComId->sub_company;
                        $payment->user_id           = $SubComId->er_id;
                        $payment->payment_user_name = $value['user_name'];
                        $payment->trans_date        = date('Y-m-d H:i:s',$value['create_at']); 
                        $payment->total_amt         = $value['pay_num'];
                        $payment->order_id          = $value['order_no'];
                        $payment->invoice_no        = $value['bill_number'];
                        $payment->payment_method    = $value['pay_type_id'];
                        $payment->package_id        = $value['package_id']; 
                        $payment->product_id        = $value['product_id'];
                        $payment->bill_number       = $value['bill_number'];
                        $payment->save();
                      
                        $payment_remove = PaymentQuery::where('payment_user_name',$value['user_name'])->where('total_amt',$value['pay_num'])->whereNull('bill_number')->delete();
                    }
                }
            }
          
            foreach($decode['data'] as $value)
            {
                if(!empty($value['order_no'])){
                    $check_tran = PaymentNew::where('order_id', $value['order_no'])->where('admin_status', '1')->first();
                  
                    if(!empty($check_tran)){
                        $transaction_id = $check_tran->id;
                    }else{
                        $transaction_id = 'N/A';
                    }
                    
                    $check_pay = PaymentGatewey::where('id', $value['pay_type_id'])->first();
                    $response[] = array('trans_date' => date('Y-m-d H:i:s',$value['create_at']), 'total_amt' => $value['pay_num'], 'payment_gateway' => $check_pay->title, 'trans_id' => $transaction_id);
                }
            }
          
            if(!empty($response)){
                $total_data = $response;
            }else{
                $total_data = null;
            }
          
            $Paydata = PaymentQuery::join('payment_gateweys','payment_gateweys.id','=','payment_query.payment_method')
                            ->where('payment_query.payment_user_name',$user_name)
                            ->select('payment_query.*','payment_gateweys.title as payment_gateway')->orderBy('trans_date', 'DESC')
                            ->get();
            if($Paydata)
            {
                $Paydatasuccess = PaymentNew::join('payment_gateweys','payment_gateweys.id','=','payment_new.payment_method')
                            ->where('payment_new.payment_user_name',$user_name)->where('admin_status', '1')
                            ->select('payment_new.*','payment_gateweys.title as payment_gateway')->orderBy('trans_date', 'DESC')
                            ->get();
                            
                return response()->json(['status'=>200,'message'=>'Query Get Successfully!','query'=>$total_data,'pages'=>$page]);
            }
            else
            {
                return response()->json(['status'=>400,'message'=>'Cant find any data in our record!']);
            }
        }else
        {
            return response()->json(['status'=>400,'message'=>'Cant find any data in our record!']);
        }
    }
  
  public function paymentfailedRecords(Request $request)
  {
      $access_token = $this->gettoken(); //get_token history
      $user_name    = request('username');
      $page         = request('page');
      $data         = "access_token={$access_token}&user_name={$user_name}&page={$page}";
      $url          = "api/v1/financial/payment-records?".$data; 
      $output       = $this->GetFunction($url);
     
      $decode       = json_decode($output,true);
      $page         = $decode['_meta'];
      $value         = $decode['data'];
      if(count($decode['data']) > '0')
      {  
          foreach($decode['data'] as $value)
          {
             $check = PaymentQuery::whereNotNull('bill_number')->first();
             
              if(empty($check))
              {
                  $SubComId = MbtBindUser::where('user_name',$user_name)->first(['sub_company','er_id']);
                  $payment  = new PaymentQuery();
                  $payment->sub_com_id        = $SubComId->sub_company;
                  $payment->user_id           = $SubComId->er_id;
                  $payment->payment_user_name = $value['user_name'];
                  $payment->trans_date        = date('Y-m-d H:i:s',$value['create_at']); 
                  $payment->total_amt         = $value['pay_num'];
                  $payment->order_id          = $value['order_no'];
                  $payment->invoice_no        = $value['bill_number'];
                  $payment->payment_method    = $value['pay_type_id'];
                  $payment->package_id        = $value['package_id']; 
                  $payment->product_id        = $value['product_id'];
                  $payment->bill_number        = $value['bill_number'];
                  $payment->save();
                  
                  $payment_remove = PaymentQuery::where('payment_user_name',$value['user_name'])->where('total_amt',$value['pay_num'])->whereNull('bill_number')->delete();
              }else
              {
                  $check_repeat = PaymentQuery::where('bill_number',$value['bill_number'])->first();
                  if(empty($check_repeat)){
                      $SubComId = MbtBindUser::where('user_name',$user_name)->first(['sub_company','er_id']);
                      $payment  = new PaymentQuery();
                      $payment->sub_com_id        = $SubComId->sub_company;
                      $payment->user_id           = $SubComId->er_id;
                      $payment->payment_user_name = $value['user_name'];
                      $payment->trans_date        = date('Y-m-d H:i:s',$value['create_at']); 
                      $payment->total_amt         = $value['pay_num'];
                      $payment->order_id          = $value['order_no'];
                      $payment->invoice_no        = $value['bill_number'];
                      $payment->payment_method    = $value['pay_type_id'];
                      $payment->package_id        = $value['package_id']; 
                      $payment->product_id        = $value['product_id'];
                      $payment->bill_number        = $value['bill_number'];
                      $payment->save();
                      
                      $payment_remove = PaymentQuery::where('payment_user_name',$value['user_name'])->where('total_amt',$value['pay_num'])->whereNull('bill_number')->delete();
                  }
              }
          }
          
          $Paydata = PaymentQuery::join('payment_gateweys','payment_gateweys.id','=','payment_query.payment_method')
                            ->where('payment_query.payment_user_name',$user_name)
                            ->select('payment_query.*','payment_gateweys.title as payment_gateway')->orderBy('trans_date', 'DESC')
                            ->get();
            if($Paydata)
            {
                $Paydatafail = PaymentNew::join('payment_gateweys','payment_gateweys.id','=','payment_new.payment_method')
                            ->where('payment_new.payment_user_name',$user_name)->where('admin_status', '2')
                            ->select('payment_new.*','payment_gateweys.title as payment_gateway')->orderBy('trans_date', 'DESC')
                            ->get();
                
                foreach($Paydatafail as $value)
                {
                    $response[] = array('trans_date' => date('Y-m-d H:i:s',strtotime($value->created_at)), 'total_amt' => $value->total_amt, 'payment_gateway' => $value->payment_gateway, 'trans_id' => $value->id);
                }
                
                if(!empty($response)){
                    $total_data = $response;
                }else{
                    $total_data = null;
                }
                            
               return response()->json(['status'=>200,'message'=>'Query Get Successfully!','query'=>$total_data,'pages'=>$page]);
            }
            else
            {
              return response()->json(['status'=>400,'message'=>'Cant find any data in our record!']);
            }
     }else
     {
         return response()->json(['status'=>400,'message'=>'Cant find any data in our record!']);
     }
  }
  
  public function bindMobileNumber()
  {
      $access_token = $this->gettoken(); //get_token
      $user_name    = request('username'); 
      $phone        = request('phone');
      $data         = "access_token={$access_token}&user_name={$user_name}&phone={$phone}";
      $url          = "api/v1/user/send-code?".$data; 
      $output       = $this->GetFunction($url);
      print_r($output);
     //dd($output);
  }
  //Get Query Order Product
  public function QueryOrderProduct()
  {
      $access_token = $this->gettoken(); //get_token
      $user_name    = request('username'); 
      $phone        = request('phone');
      $data         = "access_token={$access_token}&user_name={$user_name}";
      $url          = "api/v1/package/users-packages?".$data; 
      $output       = $this->GetFunction($url);
      print_r($output);
     //dd($output);
  }
  //Send Notification
  public function SendNotification()
  {
     // $access_token = $this->gettoken(); //get_token
     // $dataobj["access_token"] = $access_token;
    //  $dataobj["account"]      = 'Mbt00625';
    //  $dataobj["products_id"]  = request('products_id');  
    //  $dataobj["receive_type"] = 'client';
    //  $dataobj["subject"]      = request('subject');
     // $url                     = "api/v1/message/notice"; 
     // $output                  = $this->PostFunction($url,$dataobj);
     // print_r($output);
     ////dd($output);
  }
  //Find Product Operators
  public function ProductOperators()
  {
      $access_token = $this->gettoken(); //get_token
      $dataobj["access_token"]    = $access_token;
      $dataobj["user_name"]       = request('username');
      $dataobj["products_id"]     = request('products_id'); 
      $dataobj["mobile_phone"]    = '';
      $dataobj["mobile_password"] = '';
      $url                        = "api/v1/product/operators"; 
      $output                     = $this->PostFunction($url,$dataobj);
      print_r($output);
     //dd($output);
  }
  //Add Group
  public function AddGroup()
  {
      $access_token = $this->gettoken(); //get_token
      $dataobj["access_token"]    = $access_token;
      $dataobj["parent_name"]     = request('parent_name');
      $dataobj["name"]            = request('name');
      $url          = "api/v1/groups"; 
      $output       = $this->PostFunction($url,$dataobj);
      print_r($output);
  }
  // View All Groups
  public function ViewAllGroups()
  {
      $access_token = $this->gettoken(); //get_token  
      $data         = "access_token={$access_token}&per-page=30";
      $url          = "api/v1/groups?".$data; 
      $output       = $this->GetFunction($url);
      print_r($output);
  }
  //Add Billing
  public function AddBilling()
  {
      $access_token = $this->gettoken(); //get_token
      //dd($access_token);
      $dataobj["access_token"]    = $access_token;
      $dataobj["billing_name"]    = request('billing_name');
      $dataobj["billing_num"]     = request('billing_num');
      $dataobj["billing_units"]   = request('billing_units');
      $dataobj["billing_rate"]    = request('billing_rate');
      $dataobj["no_waiting_checkout"]   = request('no_waiting_checkout');
      $dataobj["billing_top"]           = request('billing_top');
      $dataobj["traffic_carry"]         = request('traffic_carry');
      $dataobj["change_mode"]           = request('change_mode');
      $dataobj["traffic_down_ration"]   = request('traffic_down_ration');
      $dataobj["traffic_up_ration"]     = request('traffic_up_ration');
      $dataobj["condition"]             = request('condition');
      $dataobj["err_msg"]               = request('err_msg');
      $dataobj["normal_msg"]            = request('normal_msg');
      $dataobj["is_step"]               = request('is_step');

        $url          = "api/v1/strategy/billing-create"; 
      $output       = $this->PostFunction($url,$dataobj);
      print_r($output);
  }
  //Create Control
    public function CreateControl()
    {
      $access_token = $this->gettoken(); //get_token
      $dataobj["access_token"]    = $access_token;
        $url          = "api/v1/strategy/control-create"; 
      $output       = $this->PostFunction($url,$dataobj);
      print_r($output);
      
    }
  
    public function boundDevices()
    {
        $user_name = "fmi02232";
        $access_token = 'pqSIP3OGX9PLSUSx3IpO2gTXM1GDJW60'; //get_token生成的token
        $dataS = "access_token={$access_token}&user_name={$user_name}";
        $url = $this->base_url."api/v1/base/macs?".$dataS; 
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
          echo $output;
        };
        curl_close($ch);
    }
  // Get Package Information
//   public function GetPackageInformation()
//   {
//       $access_token = $this->gettoken(); //get_token  
//       $user_name    = request('user_name');
//       $data         = "access_token={$access_token}&user_name={$user_name}";
//       $url          = "api/v1/package/users-packages?".$data; 
//       $output       = $this->GetFunction($url);
//       $decode       = json_decode($output,true);
//       $d            = $decode['data'];
//       $nirbhay      = array_values($d);
//       $manish       =$nirbhay[0];
//      //dd($data);
//       $MbtUser      = MbtBindUser::where('user_name',$user_name)->first();
//       $UserAvailable = array('Normal','Disabled','Stop Product','Paused','Non Activated');
     
//         $Currurl             = "api/v1/user/view?".$data; 
        
//       $Curroutput          = $this->GetFunction($Currurl);
//       $CurrDecode          = json_decode($Curroutput,true);
//       //dd($CurrDecode);
      
//      // print_r($manish['billing_name']);
//      // die();
     
//      if(empty($CurrDecode['data']['Arrears_days'])){
//          $arrears_days = 0;
//      }else{
//          $arrears_days = $CurrDecode['data']['Arrears_days'];
//      }
      
//         if($MbtUser)
//       {
          
//               $MbtBindUser = MbtBindUser::where('user_name',$user_name)->update([
//                  'er_id'                => $CurrDecode['data']['user_id'],
//                  'user_name'            => $CurrDecode['data']['user_name'],
//                  'user_real_name'       => $CurrDecode['data']['user_real_name'],
//                  'Monthly_Cost'         => $manish['billing_name'],
//                  'arrears_days'         => $arrears_days,
//                  'group_id'             => $CurrDecode['data']['group_id'],
//                  'region_id'            => $CurrDecode['data']['region_id'],
//                  'user_create_time'     => $CurrDecode['data']['user_create_time'],
//                  'user_update_time'     => $CurrDecode['data']['user_update_time'],
//                  'user_expire_time'     => $CurrDecode['data']['user_expire_time'],
//                  'user_status'          => $CurrDecode['data']['user_status'],
//                  'balance'              => $CurrDecode['data']['balance'],
//                  'mgr_name_create'      => $CurrDecode['data']['mgr_name_create'],
//                  'mgr_name_update'      => $CurrDecode['data']['mgr_name_update'],
//                  'user_start_time'      => $CurrDecode['data']['user_start_time'],
//                  'user_stop_time'       => $CurrDecode['data']['user_stop_time'],
//                  'phone'                => $CurrDecode['data']['phone'],
//                  'email'                => $CurrDecode['data']['email'],
//                  'create_visitor_num'   => $CurrDecode['data']['create_visitor_num'],
//                  'user_available'       => $CurrDecode['data']['user_available'],
//                  'question1'            => $CurrDecode['data']['question1'],
//                  'answer1'              => $CurrDecode['data']['answer1'],
//                  'question2'            => $CurrDecode['data']['question2'],
//                  'answer2'              => $CurrDecode['data']['answer2'],
//                  'question3'            => $CurrDecode['data']['question3'],
//                  'answer3'              => $CurrDecode['data']['answer3'],
//                  'school_type'          => $CurrDecode['data']['school_type'],
//                  'last_online'          => $CurrDecode['data']['last_online'],
//                  'last_offline'         => $CurrDecode['data']['last_offline'],
//                  'user_address'         => $CurrDecode['data']['user_address'],
//                  'salesman'             => $CurrDecode['data']['salesman'],
//                  'Area'                 => $CurrDecode['data']['Area'],
//                  'Router_type'          => $CurrDecode['data']['Router_type'],
//                  'other'                => $CurrDecode['data']['other'],
//                  'IPTV'                 => $CurrDecode['data']['IPTV'],
//                  'Installation_cost'    => $CurrDecode['data']['Installation_cost'],
//                  'Phone_number'         => $CurrDecode['data']['Phone_number'],
//                  //'city'                 => $CurrDecode['data']['city'],
//                  'GPS'                  => $CurrDecode['data']['GPS'],
//                  'Service_type'         => $CurrDecode['data']['Service_type'],
//                  'Bandwidth'            => $CurrDecode['data']['Bandwidth'],
//                  'Order_Received_Date'  => $CurrDecode['data']['Order_Received_Date'],
//                  'Promotion_type'       => $CurrDecode['data']['Promotion_type'],
//                  'Remark_Marketing'     => $CurrDecode['data']['Remark_Marketing'],
//                 //  'Monthly_Cost'         => $CurrDecode['data']['Monthly_Cost'],
//                 //  'Installation_person'  => $CurrDecode['data']['Installation_person'],
//                  'ODB_Box'              => $CurrDecode['data']['ODB_Box'],
//                  'Pon'                  => $CurrDecode['data']['Pon'],
//                  'LOID'                 => $CurrDecode['data']['LOID'],
//                  'Fiber_length'         => $CurrDecode['data']['Fiber_length'],
//                  'Initial_Contract_Validity'        => $CurrDecode['data']['Initial_Contract_Validity'],
//                  'Expected_installation_date'       => $CurrDecode['data']['Expected_installation_date'],
//                  'Installation_date'                => $CurrDecode['data']['Installation_date'],
//                  'Optical_power'            => $CurrDecode['data']['Optical_power'],
//                  'Remark'                   => $CurrDecode['data']['Remark'],
//                  'password'                 => $CurrDecode['data']['password'],
//                  'Abnormal_change'          => $CurrDecode['data']['Abnormal_change'],
//                  'User_maintenance_status'  => $CurrDecode['data']['User_maintenance_status'],
//                  'Fault_details'            => $CurrDecode['data']['Fault_details'],
//                  'Reporting_time'           => $CurrDecode['data']['Reporting_time'],
//                  'Supplementary_explanation' => $CurrDecode['data']['Supplementary_explanation'],
//                  'Marketing_maintenance'     => $CurrDecode['data']['Marketing_maintenance'],
//                  'Add_device_type'           => $CurrDecode['data']['Add_device_type'],
//                  'explanation'               => $CurrDecode['data']['explanation'],
//                  'DC_OCC'                    => $CurrDecode['data']['DC_OCC'],
//                  'LAN'                       => $CurrDecode['data']['LAN'],
//                  'delay_finance'             => $CurrDecode['data']['delay_finance'],
//                  'ODB_RX_Power'              => $CurrDecode['data']['ODB_RX_Power'],
//                  'Speed_Test'                => $CurrDecode['data']['Speed_Test'],
//                  'ODB_GPS'                   => $CurrDecode['data']['ODB_GPS'],
//                 //  'Reason_for_temporarily_unable_to_maintain' => $CurrDecode['data']['Reason_for_temporarily_unable_to_maintain'],
//                  'ALTERNATION_RECEIVE_DATE'                  => $CurrDecode['data']['ALTERNATION_RECEIVE_DATE'],
//                  'Replacement_days'                          => $CurrDecode['data']['Replacement_days'],
//                  'Nationality'                               => $CurrDecode['data']['Nationality'],
//                  'Sub_company'                               => $CurrDecode['data']['Sub_company'],
//                  'Now_package'                               => $decode['data'][0]['products_name'],
//                  'arrears_days'                              => $CurrDecode['data']['Arrears_days']

//                 ]);
                
//       }
      
//         if (array_key_exists($CurrDecode['data']['user_available'], $UserAvailable)) {
//             $SelfProfile['user_available']      = $UserAvailable[$CurrDecode['data']['user_available']];
//         }
       
//       return response()->json(['status'=>200,'message'=>$decode['message'],'expiry_date'=>$CurrDecode['data']['user_expire_time'], 'arrears_days' => $arrears_days,'user_available'=>$SelfProfile['user_available'],'data'=>$decode['data'], 'discount' => $this->discount, 'commercial_tax' => $this->commercial_tax]);
//      //print_r($output);
//       //     die();
     
//   }

    public function GetPackageInformation()
    {
      $access_token = $this->gettoken(); //get_token  
      $user_name    = request('user_name');
      $data         = "access_token={$access_token}&user_name={$user_name}";
      $url          = "api/v1/package/users-packages?".$data; 
      $output       = $this->GetFunction($url);
      $decode       = json_decode($output,true);
      $d            = $decode['data'];
      $nirbhay      = array_values($d);
      $manish       =$nirbhay[0];
     //dd($data);
      $MbtUser      = MbtBindUser::where('user_name',$user_name)->first();
      $UserAvailable = array('Normal','Disabled','Stop Product','Paused','Non Activated');
     
        $Currurl             = "api/v1/user/view?".$data; 
        
      $Curroutput          = $this->GetFunction($Currurl);
      $CurrDecode          = json_decode($Curroutput,true);

     if(empty($CurrDecode['data']['Arrears_days'])){
         $arrears_days = 0;
     }else{
         $arrears_days = $CurrDecode['data']['Arrears_days'];
     }
      
        if($MbtUser)
      {
          
               $MbtBindUser = MbtBindUser::where('user_name',$user_name)->update([
                 'er_id'                => $CurrDecode['data']['user_id'],
                 'user_name'            => $CurrDecode['data']['user_name'],
                 'user_real_name'       => $CurrDecode['data']['user_real_name'],
                 'Monthly_Cost'         => $manish['billing_name'],
                 'arrears_days'         => $arrears_days,
                 'group_id'             => $CurrDecode['data']['group_id'],
                 'region_id'            => $CurrDecode['data']['region_id'],
                 'user_create_time'     => $CurrDecode['data']['user_create_time'],
                 'user_update_time'     => $CurrDecode['data']['user_update_time'],
                 'user_expire_time'     => $CurrDecode['data']['user_expire_time'],
                 'user_status'          => $CurrDecode['data']['user_status'],
                 'balance'              => $CurrDecode['data']['balance'],
                 'mgr_name_create'      => $CurrDecode['data']['mgr_name_create'],
                 'mgr_name_update'      => $CurrDecode['data']['mgr_name_update'],
                 'user_start_time'      => $CurrDecode['data']['user_start_time'],
                 'user_stop_time'       => $CurrDecode['data']['user_stop_time'],
                 'phone'                => $CurrDecode['data']['phone'],
                 'email'                => $CurrDecode['data']['email'],
                 'create_visitor_num'   => $CurrDecode['data']['create_visitor_num'],
                 'user_available'       => $CurrDecode['data']['user_available'],
                 'question1'            => $CurrDecode['data']['question1'],
                 'answer1'              => $CurrDecode['data']['answer1'],
                 'question2'            => $CurrDecode['data']['question2'],
                 'answer2'              => $CurrDecode['data']['answer2'],
                 'question3'            => $CurrDecode['data']['question3'],
                 'answer3'              => $CurrDecode['data']['answer3'],
                 'school_type'          => $CurrDecode['data']['school_type'],
                 'last_online'          => $CurrDecode['data']['last_online'],
                 'last_offline'         => $CurrDecode['data']['last_offline'],
                 'user_address'         => $CurrDecode['data']['user_address'],
                 'salesman'             => $CurrDecode['data']['salesman'],
                 'Area'                 => $CurrDecode['data']['Area'],
                 'Router_type'          => $CurrDecode['data']['Router_type'],
                 'other'                => $CurrDecode['data']['other'],
                 'IPTV'                 => $CurrDecode['data']['IPTV'],
                 'Installation_cost'    => $CurrDecode['data']['Installation_cost'],
                 'Phone_number'         => $CurrDecode['data']['Phone_number'],
                 //'city'                 => $CurrDecode['data']['city'],
                 'GPS'                  => $CurrDecode['data']['GPS'],
                 'Service_type'         => $CurrDecode['data']['Service_type'],
                 'Bandwidth'            => $CurrDecode['data']['Bandwidth'],
                 'Order_Received_Date'  => $CurrDecode['data']['Order_Received_Date'],
                 'Promotion_type'       => $CurrDecode['data']['Promotion_type'],
                 'Remark_Marketing'     => $CurrDecode['data']['Remark_Marketing'],
                //  'Monthly_Cost'         => $CurrDecode['data']['Monthly_Cost'],
                //  'Installation_person'  => $CurrDecode['data']['Installation_person'],
                 'ODB_Box'              => $CurrDecode['data']['ODB_Box'],
                 'Pon'                  => $CurrDecode['data']['Pon'],
                 'LOID'                 => $CurrDecode['data']['LOID'],
                 'Fiber_length'         => $CurrDecode['data']['Fiber_length'],
                 'Initial_Contract_Validity'        => $CurrDecode['data']['Initial_Contract_Validity'],
                 'Expected_installation_date'       => $CurrDecode['data']['Expected_installation_date'],
                 'Installation_date'                => $CurrDecode['data']['Installation_date'],
                 'Optical_power'            => $CurrDecode['data']['Optical_power'],
                 'Remark'                   => $CurrDecode['data']['Remark'],
                 'password'                 => $CurrDecode['data']['password'],
                 'Abnormal_change'          => $CurrDecode['data']['Abnormal_change'],
                 'User_maintenance_status'  => $CurrDecode['data']['User_maintenance_status'],
                 'Fault_details'            => $CurrDecode['data']['Fault_details'],
                 'Reporting_time'           => $CurrDecode['data']['Reporting_time'],
                 'Supplementary_explanation' => $CurrDecode['data']['Supplementary_explanation'],
                 'Marketing_maintenance'     => $CurrDecode['data']['Marketing_maintenance'],
                 'Add_device_type'           => $CurrDecode['data']['Add_device_type'],
                 'explanation'               => $CurrDecode['data']['explanation'],
                 'DC_OCC'                    => $CurrDecode['data']['DC_OCC'],
                 'LAN'                       => $CurrDecode['data']['LAN'],
                 'delay_finance'             => $CurrDecode['data']['delay_finance'],
                 'ODB_RX_Power'              => $CurrDecode['data']['ODB_RX_Power'],
                 'Speed_Test'                => $CurrDecode['data']['Speed_Test'],
                 'ODB_GPS'                   => $CurrDecode['data']['ODB_GPS'],
                //  'Reason_for_temporarily_unable_to_maintain' => $CurrDecode['data']['Reason_for_temporarily_unable_to_maintain'],
                 'ALTERNATION_RECEIVE_DATE'                  => $CurrDecode['data']['ALTERNATION_RECEIVE_DATE'],
                 'Replacement_days'                          => $CurrDecode['data']['Replacement_days'],
                 'Nationality'                               => $CurrDecode['data']['Nationality'],
                 'Sub_company'                               => $CurrDecode['data']['Sub_company'],
                 'Now_package'                               => $decode['data'][0]['products_name'],
                 'arrears_days'                              => $CurrDecode['data']['Arrears_days']

                ]);
                
      }
      
        if (array_key_exists($CurrDecode['data']['user_available'], $UserAvailable)) {
            $SelfProfile['user_available']      = $UserAvailable[$CurrDecode['data']['user_available']];
        }
       
        return response()->json([
            'status'=>200,
            'message'=>$decode['message'],
            'expiry_date'=>$CurrDecode['data']['user_expire_time'], 
            'installation_date'=>$CurrDecode['data']['Installation_date'], 
            'arrears_days' => $arrears_days,
            'user_available'=>$SelfProfile['user_available'],
            'data'=>$decode['data'], 
            'discount' => $this->discount, 
            'commercial_tax' => $this->commercial_tax,
            'new_user_days' => $this->new_user_days,
            'new_user_days1' => $this->new_user_days1,
        ]);
    }
  
  // Store Failure Reports
  public function StoreFailureReports(Request $request)
  {
        $val        = ErrorCode::where('key',$request->fault_details)->first()->value??'';
        $MbtUser    = MbtBindUser::where('er_id',$request->bind_user_id)->first();
        $NormalUser = User::where('bind_user_id',$request->bind_user_id)->latest('updated_at')->first()->id;
        $FaultReportQuery                   = new FaultReportQuery();
        $FaultReportQuery->user_id          = $NormalUser??'';
        $FaultReportQuery->sub_com_id       = $MbtUser->Sub_company??"";
        $FaultReportQuery->fault_number     = $request->contact_phone??"";
        $FaultReportQuery->report_date      = date('Y-m-d');
        
        $FaultReportQuery->mbt_id           = $request->mbt_id??"";
    
        $FaultReportQuery->fault_address    = $MbtUser->user_address??''; 
        $FaultReportQuery->fault_details    = $val;
        $FaultReportQuery->save();
       return response()->json(['status'=>200,'message'=>'User report add successfully!']);
  }
  
  public function GetFailureReports(Request $request)
  {
       $validator = \Validator::make($request->all(),
           [
               'bind_user_id'   => 'required'
           ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
         $status = [0=>'Accept',1=>'Processing',2=>'Complete',3=>'Not Accept'];
         $NormalUser = User::where('bind_user_id',$request->bind_user_id)->latest('updated_at')->first()->id;
         $FaultReportQuery = FaultReportQuery::where('user_id',$NormalUser)->get();
         if(count($FaultReportQuery) > 0)
         {
             foreach($FaultReportQuery as $value)
             {
                   if (array_key_exists($value->fault_status, $status)){
                       $value->fault_status = $status[$value->fault_status]; 
                    }
             }
             return response()->json(['status'=>200,'message'=>'User report get successfully!','report_data'=>$FaultReportQuery]);
         }else
         {
             return response()->json(['status'=>400,'message'=>'cant find any report query!']);
         }
  }
   
    // Get All Message
  public function GetMessage(Request $request)
  {
      
       $validator = \Validator::make($request->all(),
       [
           'install_user_id'   => 'required'
       ]);
       if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
         $notification = Notification::where('install_user_id',request('install_user_id'))->get();
         if(count($notification) > 0)
         {
             return response()->json(['status'=>200,'message'=>'User notification get successfully!','notification_data'=>$notification]);
         }else
         {
             return response()->json(['status'=>400,'message'=>'cant find any notification!']);
         }
  } 
  
  // Update status 
  public function updatenoti(Request $request)
  {
    
      $validator = \Validator::make($request->all(),
      [
          'account_id'   => 'required'
      ]);
      if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
     $update = DB::table('notification')->where('account_id',request('account_id'))->update(['status'=>1]);
     
     return response()->json(['status'=>200,'message'=>'Status change Successfully']);
        

        
  }
  //End Update status 
  
  
  //Get Banner
  public function GetBanner()
  {
       $banner = DB::table('testimonials')->where('rating',1)->get();
    //   if(!empty($banner))
      if(count($banner) > 0)
         {
             $url = asset("assets/front/banner/");
             return response()->json(['status'=>200,'message'=>'Get Banner successfully!','base_url'=>$url,'banner'=>$banner]);
         }else
         {
             return response()->json(['status'=>400,'message'=>'cant find any banner!']);
         }
  }
    //Get User Profile
  public function GetMySelf(Request $request)
  {
     $validator = \Validator::make($request->all(),
       [
           'account_id'   => 'required'
       ]);
       if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        $MbtUser      = MbtBindUser::where('er_id',$request->account_id)->first();
        $NorUser      = User::where('bind_user_id',$request->account_id)->first();
        $access_token = $this->gettoken(); //get_token  
      $user_name    = $MbtUser->user_name;
      $data         = "access_token={$access_token}&user_name={$user_name}";
      $url          = "api/v1/package/users-packages?".$data; 
      $output       = $this->GetFunction($url);
      $Currurl             = "api/v1/user/view?".$data; 
      $Curroutput          = $this->GetFunction($Currurl);
      $CurrDecode          = json_decode($Curroutput,true);
      $decode              = json_decode($output,true);
     // print_r($CurrDecode['data']); 
     // die;
      if($MbtUser)
      {
               $MbtBindUser = MbtBindUser::where('er_id',$request->account_id)->update([
                 'er_id'                => $CurrDecode['data']['user_id'],
                 'user_name'            => $CurrDecode['data']['user_name'],
                 'user_real_name'       => $CurrDecode['data']['user_real_name'],
                 'group_id'             => $CurrDecode['data']['group_id'],
                 'region_id'            => $CurrDecode['data']['region_id'],
                 'user_create_time'     => $CurrDecode['data']['user_create_time'],
                 'user_update_time'     => $CurrDecode['data']['user_update_time'],
                 'user_expire_time'     => $CurrDecode['data']['user_expire_time'],
                 'user_status'          => $CurrDecode['data']['user_status'],
                 'balance'              => $CurrDecode['data']['balance'],
                 'mgr_name_create'      => $CurrDecode['data']['mgr_name_create'],
                 'mgr_name_update'      => $CurrDecode['data']['mgr_name_update'],
         'user_start_time'      => $CurrDecode['data']['user_start_time'],
                 'user_stop_time'       => $CurrDecode['data']['user_stop_time'],
                 'phone'                => $CurrDecode['data']['phone'],
                 'email'                => $CurrDecode['data']['email'],
                 'create_visitor_num'   => $CurrDecode['data']['create_visitor_num'],
                 'user_available'       => $CurrDecode['data']['user_available'],
                 'question1'            => $CurrDecode['data']['question1'],
                 'answer1'              => $CurrDecode['data']['answer1'],
                 'question2'            => $CurrDecode['data']['question2'],
                 'answer2'              => $CurrDecode['data']['answer2'],
                 'question3'            => $CurrDecode['data']['question3'],
                 'answer3'              => $CurrDecode['data']['answer3'],
                 'school_type'          => $CurrDecode['data']['school_type'],
                 'last_online'          => $CurrDecode['data']['last_online'],
                 'last_offline'         => $CurrDecode['data']['last_offline'],
                 'user_address'         => $CurrDecode['data']['user_address'],
                 'salesman'             => $CurrDecode['data']['salesman'],
                 'Area'                 => $CurrDecode['data']['Area'],
                 'Router_type'          => $CurrDecode['data']['Router_type'],
                 'other'                => $CurrDecode['data']['other'],
                 'IPTV'                 => $CurrDecode['data']['IPTV'],
                 'Installation_cost'    => $CurrDecode['data']['Installation_cost'],
                 'Phone_number'         => $CurrDecode['data']['Phone_number'],
                 //'city'                 => $CurrDecode['data']['city'],
                 'GPS'                  => $CurrDecode['data']['GPS'],
                 'Service_type'         => $CurrDecode['data']['Service_type'],
                 'Bandwidth'            => $CurrDecode['data']['Bandwidth'],
         'Order_Received_Date'  => $CurrDecode['data']['Order_Received_Date'],
                 'Promotion_type'       => $CurrDecode['data']['Promotion_type'],
                 'Remark_Marketing'     => $CurrDecode['data']['Remark_Marketing'],
                 'Monthly_Cost'         => $CurrDecode['data']['Monthly_Cost'],
                //  'Installation_person'  => $CurrDecode['data']['Installation_person'],
                 'ODB_Box'              => $CurrDecode['data']['ODB_Box'],
                 'Pon'                  => $CurrDecode['data']['Pon'],
                 'LOID'                 => $CurrDecode['data']['LOID'],
                 'Fiber_length'         => $CurrDecode['data']['Fiber_length'],
                 'Initial_Contract_Validity'        => $CurrDecode['data']['Initial_Contract_Validity'],
         'Expected_installation_date'       => $CurrDecode['data']['Expected_installation_date'],
                 'Installation_date'                => $CurrDecode['data']['Installation_date'],
                 'Optical_power'            => $CurrDecode['data']['Optical_power'],
                 'Remark'                   => $CurrDecode['data']['Remark'],
                 'password'                 => $CurrDecode['data']['password'],
                 'Abnormal_change'         => $CurrDecode['data']['Abnormal_change'],
                 'User_maintenance_status'  => $CurrDecode['data']['User_maintenance_status'],
                 'Fault_details'            => $CurrDecode['data']['Fault_details'],
                 'Reporting_time'           => $CurrDecode['data']['Reporting_time'],
                 'Supplementary_explanation' => $CurrDecode['data']['Supplementary_explanation'],
                 'Marketing_maintenance'     => $CurrDecode['data']['Marketing_maintenance'],
                 'Add_device_type'           => $CurrDecode['data']['Add_device_type'],
                 'explanation'               => $CurrDecode['data']['explanation'],
                 'DC_OCC'                    => $CurrDecode['data']['DC_OCC'],
                 'LAN'                       => $CurrDecode['data']['LAN'],
                 'delay_finance'             => $CurrDecode['data']['delay_finance'],
                 'ODB_RX_Power'              => $CurrDecode['data']['ODB_RX_Power'],
                 'Speed_Test'                => $CurrDecode['data']['Speed_Test'],
                 'ODB_GPS'                   => $CurrDecode['data']['ODB_GPS'],
                //  'Reason_for_temporarily_unable_to_maintain' => $CurrDecode['data']['Reason_for_temporarily_unable_to_maintain'],
                 'ALTERNATION_RECEIVE_DATE'                  => $CurrDecode['data']['ALTERNATION_RECEIVE_DATE'],
                 'Replacement_days'                          => $CurrDecode['data']['Replacement_days'],
                 'Nationality'                               => $CurrDecode['data']['Nationality'],
                 'Sub_company'                               => $CurrDecode['data']['Sub_company'],
                 'Now_package'                               => $decode['data'][0]['products_name']
                 ]);
                
      }
      $UserAvailable = array('Normal','Disabled','Stop Product','Paused','Non Activated');
      $SelfProfile['register_phone']      = $MbtUser->phone;
      $SelfProfile['register_date']       = date('d M Y H:i:s',$MbtUser->user_create_time);
      $SelfProfile['name']                = $MbtUser->user_name;
      $SelfProfile['mbt_account_id']      = $MbtUser->re_id;
      $SelfProfile['install_address']     = $MbtUser->user_address;
      if(!empty($MbtUser->Sub_company)){
          $SelfProfile['sub_company']         = SubCompany::find($MbtUser->Sub_company)->company_name;
      }else{
          $SelfProfile['sub_company']         = '*****';
      }
     //$SelfProfile['broadband_name']     = UserQuery::where('user_id',$NorUser->id)->orderBy('apply_id','desc')->first()->contact_name??'';
      $SelfProfile['broadband_name']      = $CurrDecode['data']['user_real_name'];
      $SelfProfile['broadband_phone']     = $CurrDecode['data']['phone'].'/'.$CurrDecode['data']['Phone_number'];
      $SelfProfile['now_package']         = $decode['data'][0]['products_name'];
      $SelfProfile['monthly_cost']        = $decode['data'][0]['billing_name'];
      $SelfProfile['expiry_date']         = $CurrDecode['data']['user_expire_time'];
      $UserAvailable = array('Normal','Disabled','Stop Product','Paused','Non Activated');
      if (array_key_exists($CurrDecode['data']['user_available'], $UserAvailable)) {
            $SelfProfile['user_available']      = $UserAvailable[$CurrDecode['data']['user_available']];
        }
      
      return response()->json(['status'=>200,'message'=>'Get Self/Profile data!','profile'=>$SelfProfile]);
    }
  
  public function GetLanguage(Request $request)
  {
     
      $deviceId=$request->input('deviceId');
      $lang_id=$request->input('language_id');
      $user_id=$request->input('user_id');
     
     $validator = \Validator::make($request->all(),
      [
          'language_id'   => 'required',
          //'user_id'       => 'required'
      ]); 
      if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
      $language = DB::table('app_language')->get();
      if($request->language_id == 0)
      {
          foreach($language as $value)
          {
              $key[]      = $value->lang_string;
              $newvalue[] = $value->lang_english;
          }
         $ArrayCombine = array_combine($key,$newvalue);
      }elseif($request->language_id == 1)
      {
          foreach($language as $value)
          {
              $key[]      = $value->lang_string;
              $newvalue[] = $value->lang_chinese;
          }
         $ArrayCombine = array_combine($key,$newvalue);
      }elseif($request->language_id == 2)
      {
          foreach($language as $value)
          {
              $key[]      = $value->lang_string;
              $newvalue[] = $value->lang_burmese;
          }
         $ArrayCombine = array_combine($key,$newvalue);
      }
      else
      {
          $ArrayCombine = array();
      }   
       
       
    $update = DB::table('users')->where('id',$user_id)->update(['language_id'=>$lang_id, 'deviceId'=>$deviceId]);
     
     if($update){
         
             return response()->json(['status'=>200,'message'=>'Get language data!','language'=>$ArrayCombine]);
     }
     else{
         
             return response()->json(['status'=>200,'message'=>'Get language data!','language'=>$ArrayCombine]);
     }
    
  }
  
    public function BindUser(Request $request)
    {
        $validator = \Validator::make($request->all(),
        [
           'account_id' => 'required',
           'phone'      => 'required',
           'user_id'    => 'required'
        ]);
        
      
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
        $access_token    = $this->gettoken(); //get_token
        $access_token    = $access_token;
        $user_name       = $request->account_id;
        $phone           = $request->phone;
        
        $data            = "access_token={$access_token}&user_name={$user_name}";
        $url             = "api/v1/user/view?".$data; 
        $output          = $this->GetFunction($url);
        $decode          = json_decode($output,true);
        if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
        {    
            $UserCheck   = User::where('id',$request->user_id)->first();
            
            // $check = MbtBindUser::where(['user_name'=>$request->account_id, 'phone'=>$request->phone])->orWhere(['user_name'=>$request->account_id, 'Phone_number'=>$request->phone])->first();
            
              $check = MbtBindUser::where(['user_name'=>$request->account_id, 'phone'=>$request->phone])->orWhere(['user_name'=>$request->account_id, 'Phone_number'=>$request->phone])->first();
            
            $check = MbtBindUser::where(['user_name'=>$request->account_id, 'phone'=>$request->phone])->first();
            
            if($check && $decode['data']['user_name'] == $user_name && ($phone == $decode['data']['phone'] || $phone == $decode['data']['Phone_number']))
            {
                if($UserCheck->bind_id == '0')
                {
                    $user = User::where('id',$request->user_id)->update(['bind_user_id'=>$check->er_id,'bind_id'=>1]);
                    $userdata['MbtData'] = $mbtbinduser    = MbtBindUser::find($check->er_id);
                    $userdata['NormalData'] = User::where('id',$request->user_id)->first();
                     
                    $date=date('Y-m-d H:i:s');
                    $bind_history= DB::table('bind_history')->insert(
                        [
                            'mbt_id' => $request->account_id, 
                            'status'  => 1,
                            'bind_date' => $date,
                            'user_id' => $request->user_id,
                            'sub_company' => $mbtbinduser->Sub_company,
                        ]
                    );
                        
                    return response()->json(['status'=>200,'message'=>'User Bind Successfully','user_data'=>$userdata]);
                }else{
                     return response()->json(['status'=>201,'message'=>'Sorry you are allready bind with another account!']);
                }
            }else{
                //Insert New Bind User Data
                if($decode['data']['user_name'] == $user_name && ($phone == $decode['data']['phone'] || $phone == $decode['data']['Phone_number'])){
                    $MbtBindUser = new MbtBindUser();
                    $MbtBindUser->er_id                = $decode['data']['user_id'];
                    $MbtBindUser->user_name            = $decode['data']['user_name'];
                    $MbtBindUser->user_real_name       = $decode['data']['user_real_name'];
                    $MbtBindUser->group_id             = $decode['data']['group_id'];
                    $MbtBindUser->region_id            = $decode['data']['region_id'];
                    $MbtBindUser->user_create_time     = $decode['data']['user_create_time'];
                    $MbtBindUser->user_update_time     = $decode['data']['user_update_time'];
                    $MbtBindUser->user_expire_time     = $decode['data']['user_expire_time'];
                    $MbtBindUser->user_status          = $decode['data']['user_status'];
                    $MbtBindUser->balance              = $decode['data']['balance'];
                    $MbtBindUser->mgr_name_create      = $decode['data']['mgr_name_create'];
                    $MbtBindUser->mgr_name_update      = $decode['data']['mgr_name_update'];
                    $MbtBindUser->user_start_time      = $decode['data']['user_start_time'];
                    $MbtBindUser->user_stop_time       = $decode['data']['user_stop_time'];
                    $MbtBindUser->phone                = $decode['data']['phone'];
                    $MbtBindUser->email                = $decode['data']['email'];
                    $MbtBindUser->create_visitor_num   = $decode['data']['create_visitor_num'];
                    $MbtBindUser->user_available       = $decode['data']['user_available'];
                    $MbtBindUser->question1            = $decode['data']['question1'];
                    $MbtBindUser->answer1              = $decode['data']['answer1'];
                    $MbtBindUser->question2            = $decode['data']['question2'];
                    $MbtBindUser->answer2              = $decode['data']['answer2'];
                    $MbtBindUser->question3            = $decode['data']['question3'];
                    $MbtBindUser->answer3              = $decode['data']['answer3'];
                    $MbtBindUser->school_type          = $decode['data']['school_type'];
                    $MbtBindUser->last_online          = $decode['data']['last_online'];
                    $MbtBindUser->last_offline         = $decode['data']['last_offline'];
                    $MbtBindUser->user_address         = $decode['data']['user_address'];
                    $MbtBindUser->salesman             = $decode['data']['salesman'];
                    $MbtBindUser->Area                 = $decode['data']['Area'];
                    $MbtBindUser->Router_type          = $decode['data']['Router_type'];
                    $MbtBindUser->other                = $decode['data']['other'];
                    $MbtBindUser->IPTV                 = $decode['data']['IPTV'];
                    $MbtBindUser->Installation_cost    = $decode['data']['Installation_cost'];
                    $MbtBindUser->Phone_number         = $decode['data']['Phone_number'];
                    // $MbtBindUser->city                 = $decode['data']['city'];
                    $MbtBindUser->GPS                  = $decode['data']['GPS'];
                    $MbtBindUser->Service_type         = $decode['data']['Service_type'];
                    $MbtBindUser->Bandwidth            = $decode['data']['Bandwidth'];
                    $MbtBindUser->Order_Received_Date  = $decode['data']['Order_Received_Date'];
                    $MbtBindUser->Promotion_type       = $decode['data']['Promotion_type'];
                    $MbtBindUser->Remark_Marketing     = $decode['data']['Remark_Marketing'];
                    $MbtBindUser->Monthly_Cost         = $decode['data']['Monthly_Cost'];
                    // $MbtBindUser->Installation_person  = $decode['data']['Installation_person'];
                    $MbtBindUser->ODB_Box              = $decode['data']['ODB_Box'];
                    $MbtBindUser->Pon                  = $decode['data']['Pon'];
                    $MbtBindUser->LOID                 = $decode['data']['LOID'];
                    $MbtBindUser->Fiber_length         = $decode['data']['Fiber_length'];
                    $MbtBindUser->Initial_Contract_Validity        = $decode['data']['Initial_Contract_Validity'];
                    $MbtBindUser->Expected_installation_date       = $decode['data']['Expected_installation_date'];
                    $MbtBindUser->Installation_date                = $decode['data']['Installation_date'];
                    $MbtBindUser->Optical_power            = $decode['data']['Optical_power'];
                    $MbtBindUser->Remark                   = $decode['data']['Remark'];
                    $MbtBindUser->password                 = $decode['data']['password'];
                    $MbtBindUser->Abnormal_change          = $decode['data']['Abnormal_change'];
                    $MbtBindUser->User_maintenance_status  = $decode['data']['User_maintenance_status'];
                    $MbtBindUser->Fault_details            = $decode['data']['Fault_details'];
                    $MbtBindUser->Reporting_time           = $decode['data']['Reporting_time'];
                    $MbtBindUser->Supplementary_explanation = $decode['data']['Supplementary_explanation'];
                    $MbtBindUser->Marketing_maintenance     = $decode['data']['Marketing_maintenance'];
                    $MbtBindUser->Add_device_type           = $decode['data']['Add_device_type'];
                    $MbtBindUser->explanation               = $decode['data']['explanation'];
                    $MbtBindUser->DC_OCC                    = $decode['data']['DC_OCC'];
                    $MbtBindUser->LAN                       = $decode['data']['LAN'];
                    $MbtBindUser->delay_finance             = $decode['data']['delay_finance'];
                    $MbtBindUser->ODB_RX_Power              = $decode['data']['ODB_RX_Power'];
                    $MbtBindUser->Speed_Test                = $decode['data']['Speed_Test'];
                    $MbtBindUser->ODB_GPS                   = $decode['data']['ODB_GPS'];
                    //  $MbtBindUser->Reason_for_temporarily_unable_to_maintain = $decode['data']['Reason_for_temporarily_unable_to_maintain'];
                    $MbtBindUser->ALTERNATION_RECEIVE_DATE                  = $decode['data']['ALTERNATION_RECEIVE_DATE'];
                    $MbtBindUser->Replacement_days                          = $decode['data']['Replacement_days'];
                    $MbtBindUser->Nationality                               = $decode['data']['Nationality'];
                    $MbtBindUser->Sub_company                               = $decode['data']['Sub_company'];
                    $MbtBindUser->save();
                
                    //User ID update for relation
                    $user = User::where('id',$request->user_id)->update(['bind_user_id'=>$decode['data']['user_id'],'bind_id'=>1]);
                    $userdata['MbtData'] = $mbtbinduser   = MbtBindUser::find($decode['data']['user_id']);
                    $userdata['NormalData'] = User::where('id',$request->user_id)->first();
                    //Update Payment Query
                    $this->paymentRecordsInsert($decode['data']['user_name']);
                     
                    $date=date('Y-m-d H:i:s');
           
                    $bind_history= DB::table('bind_history')->insert(
                        [
                            'mbt_id' => $request->account_id, 
                            'status'  => 1,
                            'bind_date' => $date,
                            'user_id' => $request->user_id,
                            'sub_company' => $mbtbinduser->Sub_company,
                        ]
                    );
                    
                    return response()->json(['status'=>200,'message'=>'User Bind Successfully','user_data'=>$userdata]);
                }else{
                    return response()->json(['status'=>400,'message'=>'Invalid Phone Number']);
                }
            }
        }else{
            return response()->json(['status'=>400,'message'=>'User does not exist']);
        }
    }
    
  //Get Current Package
  public function GetPackage()
  {
      $access_token = $this->gettoken(); //get_token
      
      // If no token available, return error
      if (!$access_token) {
          return response()->json(['status'=>400,'message'=>'Unable to get access token from external service']);
      }
      
      $user_name    = request('user_name');
      $data         = "access_token={$access_token}&user_name={$user_name}";
      $url          = "api/v1/package/users-packages?".$data; 
      $output       = $this->GetFunction($url);
      $decode       = json_decode($output,true);
      $MbtUserObj   = MbtBindUser::where('user_name',$user_name)->first();
      $MbtUser      = $MbtUserObj ? $MbtUserObj->user_expire_time : '';
      if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
      {
           $PackageName = $decode['data'][0]['billing_name'];
           $explode     = explode(" ",$PackageName); //Explode package name for get price
           $takeprice   = $explode[0];  //get price from 0 index and made package for defferent time
           $data = [];
           $data['0']    = $takeprice*1; // One Month
           $data['1']    = $takeprice*3; // Three Month
           $data['2']    = $takeprice*6; // Six Month
           $data['3']    = $takeprice*12; // twelve(1 year) Month
           
            $discount = $this->discount;
            $commercial_tax = $this->commercial_tax;
            
            $GetPackageDescription = Package::where('status',1)->orderBy('discount_price','asc')->get();
            foreach($GetPackageDescription as $key=>$value)
            {
                $amount = $data[$key];
                $new_amount = ($amount/(1 + $discount/100))/(1 + $commercial_tax/100);
                $total_amount = number_format($new_amount, 0, '.', '');
                
                $value->price = $data[$key];
                $value->discounted_price = $total_amount;
            }
            return response()->json(['status'=>200,'message'=>'Get Package successfully!','package'=>$GetPackageDescription]);
           
      }else
      {
           $message = $decode['message'] ?? 'Unable to get package information';
           $version = $decode['version'] ?? '';
           return response()->json(['status'=>400,'message'=>$message,'version'=>$version]);
      }
  
  }
  
    public function getpackage_language(Request $request)
    {
        $language_id= $request->input('language_id');

        $validator = \Validator::make($request->all(),
        [
           'language_id'    => 'required'
        ]);
       
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
     
        if($language_id== 0)
        {
            $englanguage= DB::table('packages')->select('feature')->orderBy('discount_price', 'asc')->get();
            return response()->json(['status'=>200,'message'=>'English Language!','user_data'=>$englanguage]);
        }
        elseif($language_id==1)
        {
            $englanguage= DB::table('packages')->select('chinese AS feature')->orderBy('discount_price', 'asc')->get();
            return response()->json(['status'=>200,'message'=>'Chinese  Language!','user_data'=>$englanguage]);
        }
        elseif($language_id==2)
        {
            $englanguage= DB::table('packages')->select('myanmar  AS feature')->orderBy('discount_price', 'asc')->get();
            return response()->json(['status'=>200,'message'=>'Maynmar  Language!','user_data'=>$englanguage]);
        }
    }
  
  //Unbind User
  public function UnbindUser(Request $request)
  {
     $validator = \Validator::make($request->all(),
       [
           'user_id'    => 'required'
       ]);
       
     
        
       
       if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        $update = User::where('id',$request->user_id)->update(['bind_user_id'=>0,'bind_id'=>0]);
        $data['NormalData'] = User::where('id',$request->user_id)->first();
        
         $currentdate= date('Y-m-d H:i:s');
       
       $bind_history=DB::table('bind_history')->where('user_id',$request->user_id)->get();
         
         foreach($bind_history as $bind_his)
         
         if($bind_his->status==1)
         {
            $update = DB::table('bind_history')->where('status',$bind_his->status)
                                               ->where('user_id',$request->user_id)
                                               ->update(['unbind_date'=>$currentdate,'status'=>0]);
         }
         
        return response()->json(['status'=>200,'message'=>'Unbind successfully!','user_data'=>$data]);
  }
  //Updtae User on MBT server
  
    public function UpdateUser($data)
    {
        $access_token = $this->gettoken(); //get_token
        $dataobj["access_token"]      = $access_token;
        $dataobj["user_name"]         = $data['user_name'];
        $dataobj["user_expire_time"]  = $data['Expire_time']; 
        $dataobj["user_available"]    = $data['user_available'];
        $dataobj['Arrears_days']      = $data['Arrears_days'];
      
        $url                          = "api/v1/user/update"; 
        $output                       = $this->PostFunction($url,$dataobj);
        $dataview            = "access_token={$access_token}&user_name={$data['user_name']}&user_expire_time={$data['Expire_time']}";
        $urlview             = "api/v1/user/view?".$dataview; 
        $outputview          = $this->GetFunction($urlview);
        return $outputview;
  }
  
    // Store Payment
    public function StorePayment(Request $request)
    {
        $validator = \Validator::make($request->all(),
        [
           'user_name'   => 'required',
           'payment_id'  => 'required',
           'amount'      => 'required',
           'begin_date'  => 'required',
           'expire_time' => 'required',
           'phone'       => 'required',
        ]);
       
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
        $discount = $this->discount;
        $commercial_tax = $this->commercial_tax;
        $amount = $request->amount;
        
        $new_amount = ($amount/(1 + $discount/100))/(1 + $commercial_tax/100);
        $total_amount = number_format($new_amount, 0, '.', '');
        
        $phone = $request->phone;
        $data = [];
        
        $data['user_name']          = $request->user_name;
        $data['Expire_time']        = $request->expire_time;
        $data['user_available']     = 0;
        $data['Arrears_days']       = 0;
        
        $payment_new                = new PendingPayment();
        $payment_new->user_name     = $request->user_name;
        $payment_new->order_no      = uniqid();
        $payment_new->payment_id    = $request->payment_id; 
        $payment_new->amount        = $amount; 
        $payment_new->number        = uniqid(); 
        $payment_new->begin_date    = $request->begin_date;
        $payment_new->expire_time   = $request->expire_time;
        $payment_new->phone         = $request->phone;
        $payment_new->discount      = $discount;
        $payment_new->commercial_tax= $commercial_tax;
        $payment_new->save();
        
        if($payment_new->id){
            $check_payment = PendingPayment::where('id', $payment_new->id)->first();
            if($check_payment->expire_time == 'NaN-NaN-NaN 23:00:00'){
                $remove_payment = PendingPayment::where('id', $payment_new->id)->delete();
                return response()->json([
                    'status'=>405,
                    'message'=>'Something went wrong!.'
                ]);
            }
        }
          
        if($request->payment_id == '12'){
            date_default_timezone_set('Asia/Yangon');
        
            $api_url          = $this->direct_apiurl;
            $fldMerchCode     = $this->direct_mcode;
            $key              = $this->direct_key;
            
            $fldTxnCurr       = 'MMK';
            $fldTxnAmt        = $amount;
            $fldTxnScAmt      = '0';
            $fldSucStatFlg    = 'N';
            $fldFailStatFlg   = 'N';
            $fldClientCode    = '101';
            $fldMerchRefNbr   = $payment_new->order_no;
            $fldDatTimeTxn    = date("d/m/YH:i:s", strtotime('+0 hours'));
            
            $string = 'fldClientCode='.$fldClientCode.'|fldMerchCode='.$fldMerchCode.'|fldTxnCurr='.$fldTxnCurr.'|fldTxnAmt='.$fldTxnAmt.'|fldTxnScAmt='.$fldTxnScAmt.'|fldMerchRefNbr='.$fldMerchRefNbr.'|fldSucStatFlg='.$fldSucStatFlg.'|fldFailStatFlg='.$fldFailStatFlg.'|fldDatTimeTxn='.$fldDatTimeTxn;
            $md5_enc = md5($string);
            $new_string = $string.'|checkSum='.$md5_enc;
            //dd($new_string);
               
        }else{
              $key = 'N/A';
              $new_string = 'N/A';
        }
          
        return response()->json([
            'status'=>200,
            'order_no'=>$payment_new->order_no,
            'amount'=>$payment_new->amount,
            'key' => $key,
            'testdata' => $new_string,
            'message'=>'Payment request successfull.',
            'username'=>$request->user_name
        ]);
    }
  
    // public function UpdatePayment(Request $request)
    // {
    //     $check_pay = PaymentQuery::where('order_id', $request->order_no)->where('admin_status', '1')->first();
    //     if(empty($check_pay)){
    //         $pay_details = PendingPayment::where('order_no', $request->order_no)->first();
            
    //         $user_name   = $pay_details->user_name;
    //         $order_no    = $pay_details->order_no;
    //         $payment_id  = $pay_details->payment_id;
    //         $amount      = $pay_details->amount;
    //         $number      = $pay_details->number;
    //         $begin_date  = $pay_details->begin_date;
    //         $expire_time = $pay_details->expire_time;
            
    //         $access_token = $this->gettoken();
    //         $dataobj["access_token"]    = $access_token;
    //         $dataobj["user_name"]       = $user_name;
    //         $dataobj["order_no"]        = $order_no;
    //         $dataobj['pay_type_id']     = $payment_id; 
    //         $dataobj['pay_num']         = $amount;  
    //         $dataobj['number']          = $number;  
    //         $dataobj['Begin_date']      = $begin_date;
    //         $dataobj['Expire_time']     = $expire_time;
    //         $invoice_no = $dataobj['number'];
            
    //         $url                        = "api/v1/product/recharge"; 
    //         $output                     = $this->PostFunction($url,$dataobj);
    //         $decode                     = json_decode($output,true);
    //         $data = [];
    //         if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //         {
    //             $data['access_token']       = $access_token;
    //             $data['user_name']          = $pay_details->user_name;
    //             $data['Expire_time']        = $pay_details->expire_time;
    //             $data['user_available']     = 0;
    //             $data['Arrears_days']       = 0;
              
    //             $UpadteUser = $this->UpdateUser($data);
              
    //             $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                  
    //             $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                
    //             $rs = PaymentNew::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
    //             $rs1 = PaymentQuery::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
                
    //             return response()->json([
    //                 'status'=>200,
    //                 'message'=>'Order updated successfully.'
    //             ]);
    //         }else
    //         {
    //           return response()->json(['status'=>400,'message'=>$decode['message']]);
    //         }
    //     }else{
    //         return response()->json([
    //             'status'=>200,
    //             'message'=>'Order updated successfully.'
    //         ]);
    //     }
    // }
    
    // public function UpdatePayment(Request $request)
    // {
    //     $check_pay = PaymentQuery::where('order_id', $request->order_no)->where('admin_status', '1')->first();
    //     if(empty($check_pay)){
    //         $pay_details = PendingPayment::where('order_no', $request->order_no)->first();
            
    //         $user_name   = $pay_details->user_name;
    //         $order_no    = $pay_details->order_no;
    //         $payment_id  = $pay_details->payment_id;
    //         $amount      = $pay_details->amount;
    //         $number      = $pay_details->number;
    //         $begin_date  = $pay_details->begin_date;
    //         $expire_time = $pay_details->expire_time;
            
    //         if($pay_details->payment_id == '10'){
                
    //             $payment = WaveCallback::where('orderId', $order_no)->first();
                
    //             if($payment->status == 'PAYMENT_CONFIRMED'){
                
    //                 $access_token = $this->gettoken();
    //                 $dataobj["access_token"]    = $access_token;
    //                 $dataobj["user_name"]       = $user_name;
    //                 $dataobj["order_no"]        = $order_no;
    //                 $dataobj['pay_type_id']     = $payment_id; 
    //                 $dataobj['pay_num']         = $amount;  
    //                 $dataobj['number']          = $number;  
    //                 $dataobj['Begin_date']      = $begin_date;
    //                 $dataobj['Expire_time']     = $expire_time;
    //                 $invoice_no = $dataobj['number'];
                    
    //                 $url                        = "api/v1/product/recharge"; 
    //                 $output                     = $this->PostFunction($url,$dataobj);
    //                 $decode                     = json_decode($output,true);
    //                 $data = [];
    //                 if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //                 {
    //                     $data['access_token']       = $access_token;
    //                     $data['user_name']          = $pay_details->user_name;
    //                     $data['Expire_time']        = $pay_details->expire_time;
    //                     $data['user_available']     = 0;
    //                     $data['Arrears_days']       = 0;
                      
    //                     $UpadteUser = $this->UpdateUser($data);
                      
    //                     $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                          
    //                     $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                        
    //                     $rs = PaymentNew::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
    //                     $rs1 = PaymentQuery::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
                        
    //                     return response()->json([
    //                         'status'=>200,
    //                         'message'=>'Order updated successfully.'
    //                     ]);
    //                 }else
    //                 {
    //                   return response()->json(['status'=>400,'message'=>$decode['message']]);
    //                 }
    //             }else{
    //                 return response()->json(['status'=>400,'message'=>'Payment not completed.']);
    //             }
                
    //         }elseif($pay_details->payment_id == '8'){
                
    //             $api_url          = $this->kbz_api_url.'/queryorder';
    //             $m_code           = $this->kbz_m_code;
    //             $appid            = $this->kbz_appid;
    //             $key              = $this->kbz_key;
    //             $trade_type       = $this->kbz_trade_type;
                
    //             $timestamp        = time();
    //             $nonce            = $this->generateRandomString();
    //             $method           = "kbz.payment.queryorder";
    //             $sign_type        = "SHA256";
    //             $version          = "3.0";
    //             $oreder_id        = $request->order_no;
    //             $c_time           = $timestamp;
    //             $nonce_str        = $nonce;
                
    //             $stringA = "appid=".$appid."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&timestamp=".$c_time."&version=".$version;
                
    //             $stringToSign = $stringA."&key=".$key;
                
    //             $signature = strtoupper(hash('sha256', $stringToSign));
                
    //             $kbz_params = [
    //                 "Request" => array(
    //                     "timestamp" => $c_time,
    //                     "nonce_str" => $nonce_str,
    //                     "method" => $method, 
    //                     "sign_type" => $sign_type, 
    //                     "sign" => $signature,
    //                     "version" => $version,
    //                     "biz_content" => array(
    //                         "appid" => $appid,
    //                         "merch_code" => $m_code, 
    //                         "merch_order_id" => $oreder_id
    //                     )
    //                 )
    //             ];
                
    //             $ch = curl_init();
    //             curl_setopt($ch, CURLOPT_URL, $api_url);
    //             curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //               "Content-Type: application/json"
    //             ]);
    //             curl_setopt($ch, CURLOPT_POST, 1);
    //             curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
    //             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //             curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    //             curl_setopt($ch, CURLOPT_TIMEOUT, 80);
    //             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //             $result = curl_exec($ch);
    //             $response = json_decode($result, true);
    //           // dd($response['Response']['result']);
                
    //             if($response['Response']['result'] == 'SUCCESS' && $response['Response']['trade_status'] == 'PAY_SUCCESS'){
    //                 $rs = PaymentNew::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
    //                 $rs1 = PaymentQuery::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
                    
    //                 $access_token = $this->gettoken();
    //                 $dataobj["access_token"]    = $access_token;
    //                 $dataobj["user_name"]       = $user_name;
    //                 $dataobj["order_no"]        = $order_no;
    //                 $dataobj['pay_type_id']     = $payment_id; 
    //                 $dataobj['pay_num']         = $amount;  
    //                 $dataobj['number']          = $number;  
    //                 $dataobj['Begin_date']      = $begin_date;
    //                 $dataobj['Expire_time']     = $expire_time;
    //                 $invoice_no = $dataobj['number'];
                    
    //                 $url                        = "api/v1/product/recharge"; 
    //                 $output                     = $this->PostFunction($url,$dataobj);
    //                 $decode                     = json_decode($output,true);
    //                 $data = [];
    //                 if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //                 {
    //                     $data['access_token']       = $access_token;
    //                     $data['user_name']          = $pay_details->user_name;
    //                     $data['Expire_time']        = $pay_details->expire_time;
    //                     $data['user_available']     = 0;
    //                     $data['Arrears_days']       = 0;
                      
    //                     $UpadteUser = $this->UpdateUser($data);
                      
    //                     $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                          
    //                     $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                        
    //                     $rs = PaymentNew::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
    //                     $rs1 = PaymentQuery::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
                        
    //                     return response()->json([
    //                         'status'=>200,
    //                         'message'=>'Order updated successfully.'
    //                     ]);
    //                 }else
    //                 {
    //                   return response()->json(['status'=>400,'message'=>$decode['message']]);
    //                 }
    //             }
                
    //         }else{
    //             $access_token = $this->gettoken();
    //             $dataobj["access_token"]    = $access_token;
    //             $dataobj["user_name"]       = $user_name;
    //             $dataobj["order_no"]        = $order_no;
    //             $dataobj['pay_type_id']     = $payment_id; 
    //             $dataobj['pay_num']         = $amount;  
    //             $dataobj['number']          = $number;  
    //             $dataobj['Begin_date']      = $begin_date;
    //             $dataobj['Expire_time']     = $expire_time;
    //             $invoice_no = $dataobj['number'];
                
    //             $url                        = "api/v1/product/recharge"; 
    //             $output                     = $this->PostFunction($url,$dataobj);
    //             $decode                     = json_decode($output,true);
    //             $data = [];
    //             if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //             {
    //                 $data['access_token']       = $access_token;
    //                 $data['user_name']          = $pay_details->user_name;
    //                 $data['Expire_time']        = $pay_details->expire_time;
    //                 $data['user_available']     = 0;
    //                 $data['Arrears_days']       = 0;
                  
    //                 $UpadteUser = $this->UpdateUser($data);
                  
    //                 $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                      
    //                 $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                    
    //                 $rs = PaymentNew::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
    //                 $rs1 = PaymentQuery::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
                    
    //                 return response()->json([
    //                     'status'=>200,
    //                     'message'=>'Order updated successfully.'
    //                 ]);
    //             }else
    //             {
    //               return response()->json(['status'=>400,'message'=>$decode['message']]);
    //             }
    //         }
    //     }else{
    //         return response()->json([
    //             'status'=>200,
    //             'message'=>'Order updated successfully.'
    //         ]);
    //     }
    // }
    
    // public function UpdatePayment(Request $request)
    // {
    //     $check_pay = PaymentQuery::where('order_id', $request->order_no)->where('admin_status', '1')->first();
    //     if(empty($check_pay)){
    //         $pay_details = PendingPayment::where('order_no', $request->order_no)->first();
            
    //         $user_name   = $pay_details->user_name;
    //         $order_no    = $pay_details->order_no;
    //         $payment_id  = $pay_details->payment_id;
    //         $amount      = $pay_details->amount;
    //         $number      = $pay_details->number;
    //         $begin_date  = $pay_details->begin_date;
    //         $expire_time = $pay_details->expire_time;
            
    //         if($pay_details->payment_id == '10'){
                
    //             $payment = WaveCallback::where('orderId', $order_no)->first();
                
    //             if($payment->status == 'PAYMENT_CONFIRMED'){
                
    //                 $access_token = $this->gettoken();
    //                 $dataobj["access_token"]    = $access_token;
    //                 $dataobj["user_name"]       = $user_name;
    //                 $dataobj["order_no"]        = $order_no;
    //                 $dataobj['pay_type_id']     = $payment_id; 
    //                 $dataobj['pay_num']         = $amount;  
    //                 $dataobj['number']          = $number;  
    //                 $dataobj['Begin_date']      = $begin_date;
    //                 $dataobj['Expire_time']     = $expire_time;
    //                 $invoice_no = $dataobj['number'];
                    
    //                 $url                        = "api/v1/product/recharge"; 
    //                 $output                     = $this->PostFunction($url,$dataobj);
    //                 $decode                     = json_decode($output,true);
    //                 $data = [];
    //                 if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //                 {
    //                     $data['access_token']       = $access_token;
    //                     $data['user_name']          = $pay_details->user_name;
    //                     $data['Expire_time']        = $pay_details->expire_time;
    //                     $data['user_available']     = 0;
    //                     $data['Arrears_days']       = 0;
                      
    //                     $UpadteUser = $this->UpdateUser($data);
                      
    //                     $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                          
    //                     $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                        
    //                     $rs = PaymentNew::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
    //                     $rs1 = PaymentQuery::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
                        
    //                     PendingPayment::where('order_no', $order_no)->update(['status' => '1']);
                        
    //                     return response()->json([
    //                         'status'=>200,
    //                         'message'=>'Order updated successfully.'
    //                     ]);
    //                 }else
    //                 {
    //                   return response()->json(['status'=>400,'message'=>$decode['message']]);
    //                 }
    //             }else{
    //                 return response()->json([
    //                     'status'=>400,
    //                     'message'=>'Payment not done.'
    //                 ]);
    //             }
                
    //         }elseif($pay_details->payment_id == '8'){
                
    //             $api_url          = $this->kbz_api_url.'/queryorder';
    //             $m_code           = $this->kbz_m_code;
    //             $appid            = $this->kbz_appid;
    //             $key              = $this->kbz_key;
    //             $trade_type       = $this->kbz_trade_type;
                
    //             $timestamp        = time();
    //             $nonce            = $this->generateRandomString();
    //             $method           = "kbz.payment.queryorder";
    //             $sign_type        = "SHA256";
    //             $version          = "3.0";
    //             $oreder_id        = $request->order_no;
    //             $c_time           = $timestamp;
    //             $nonce_str        = $nonce;
                
    //             $stringA = "appid=".$appid."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&timestamp=".$c_time."&version=".$version;
                
    //             $stringToSign = $stringA."&key=".$key;
                
    //             $signature = strtoupper(hash('sha256', $stringToSign));
                
    //             $kbz_params = [
    //                 "Request" => array(
    //                     "timestamp" => $c_time,
    //                     "nonce_str" => $nonce_str,
    //                     "method" => $method, 
    //                     "sign_type" => $sign_type, 
    //                     "sign" => $signature,
    //                     "version" => $version,
    //                     "biz_content" => array(
    //                         "appid" => $appid,
    //                         "merch_code" => $m_code, 
    //                         "merch_order_id" => $oreder_id
    //                     )
    //                 )
    //             ];
                
    //             $ch = curl_init();
    //             curl_setopt($ch, CURLOPT_URL, $api_url);
    //             curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //               "Content-Type: application/json"
    //             ]);
    //             curl_setopt($ch, CURLOPT_POST, 1);
    //             curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
    //             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //             curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    //             curl_setopt($ch, CURLOPT_TIMEOUT, 80);
    //             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //             $result = curl_exec($ch);
    //             $response = json_decode($result, true);
    //           // dd($response['Response']['result']);
                
    //             if($response['Response']['result'] == 'SUCCESS' && $response['Response']['trade_status'] == 'PAY_SUCCESS'){
    //                 $rs = PaymentNew::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
    //                 $rs1 = PaymentQuery::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
                    
    //                 $access_token = $this->gettoken();
    //                 $dataobj["access_token"]    = $access_token;
    //                 $dataobj["user_name"]       = $user_name;
    //                 $dataobj["order_no"]        = $oreder_id;
    //                 $dataobj['pay_type_id']     = $payment_id; 
    //                 $dataobj['pay_num']         = $amount;  
    //                 $dataobj['number']          = $number;  
    //                 $dataobj['Begin_date']      = $begin_date;
    //                 $dataobj['Expire_time']     = $expire_time;
    //                 $invoice_no = $dataobj['number'];
                    
    //                 $url                        = "api/v1/product/recharge"; 
    //                 $output                     = $this->PostFunction($url,$dataobj);
    //                 $decode                     = json_decode($output,true);
    //                 $data = [];
    //                 if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //                 {
    //                     $data['access_token']       = $access_token;
    //                     $data['user_name']          = $pay_details->user_name;
    //                     $data['Expire_time']        = $pay_details->expire_time;
    //                     $data['user_available']     = 0;
    //                     $data['Arrears_days']       = 0;
                      
    //                     $UpadteUser = $this->UpdateUser($data);
                      
    //                     $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                          
    //                     $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                        
    //                     PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                        
    //                     $rs = PaymentNew::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
    //                     $rs1 = PaymentQuery::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
                        
    //                     return response()->json([
    //                         'status'=>200,
    //                         'message'=>'Order updated successfully.'
    //                     ]);
    //                 }else
    //                 {
    //                   return response()->json(['status'=>400,'message'=>$decode['message']]);
    //                 }
    //             }else{
    //                 return response()->json([
    //                     'status'=>400,
    //                     'message'=>'Payment not done.'
    //                 ]);
    //             }
                
    //         }elseif($pay_details->payment_id == '11'){
                
    //             $oreder_id        = $request->order_no;
    //             $oreder_ref       = $request->generateRefOrder;
        
    //             $api_url          = $this->api_url.'/checkstatus-webpayment.service';
    //             $ecommerce_id     = $this->ecommerce_id;
                
    //             $cb_params = [
    //                 "generateRefOrder" => $oreder_ref,
    //                 "ecommerceId" => $ecommerce_id,
    //                 "orderId" => $oreder_id
    //             ];
                
    //             $ch = curl_init();
    //             curl_setopt($ch, CURLOPT_URL, $api_url);
    //             curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //               "Content-Type: application/json"
    //             ]);
    //             curl_setopt($ch, CURLOPT_POST, 1);
    //             curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cb_params));
    //             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //             curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    //             curl_setopt($ch, CURLOPT_TIMEOUT, 80);
    //             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //             $result = curl_exec($ch);
    //             $response = json_decode($result, true);
    //             //dd($response);
                
    //             if(!empty($response['transactionStatus']) && $response['transactionStatus'] == 'S'){
    //                 $rs = PaymentNew::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
    //                 $rs1 = PaymentQuery::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
                    
    //                 $access_token = $this->gettoken();
    //                 $dataobj["access_token"]    = $access_token;
    //                 $dataobj["user_name"]       = $user_name;
    //                 $dataobj["order_no"]        = $oreder_id;
    //                 $dataobj['pay_type_id']     = $payment_id; 
    //                 $dataobj['pay_num']         = $amount;  
    //                 $dataobj['number']          = $number;  
    //                 $dataobj['Begin_date']      = $begin_date;
    //                 $dataobj['Expire_time']     = $expire_time;
    //                 $invoice_no = $dataobj['number'];
                    
    //                 $url                        = "api/v1/product/recharge"; 
    //                 $output                     = $this->PostFunction($url,$dataobj);
    //                 $decode                     = json_decode($output,true);
    //                 $data = [];
    //                 if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //                 {
    //                     $data['access_token']       = $access_token;
    //                     $data['user_name']          = $pay_details->user_name;
    //                     $data['Expire_time']        = $pay_details->expire_time;
    //                     $data['user_available']     = 0;
    //                     $data['Arrears_days']       = 0;
                      
    //                     $UpadteUser = $this->UpdateUser($data);
                      
    //                     $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                          
    //                     $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                        
    //                     PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                        
    //                     $rs = PaymentNew::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
    //                     $rs1 = PaymentQuery::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
                        
    //                     return response()->json([
    //                         'status'=>200,
    //                         'message'=>'Order updated successfully.'
    //                     ]);
    //                 }else
    //                 {
    //                   return response()->json(['status'=>400,'message'=>$decode['message']]);
    //                 }
    //             }else{
    //                 return response()->json([
    //                     'status'=>400,
    //                     'message'=>'Payment not done.'
    //                 ]);
    //             }
                
    //         }
    //         // else{
    //         //     $access_token = $this->gettoken();
    //         //     $dataobj["access_token"]    = $access_token;
    //         //     $dataobj["user_name"]       = $user_name;
    //         //     $dataobj["order_no"]        = $order_no;
    //         //     $dataobj['pay_type_id']     = $payment_id; 
    //         //     $dataobj['pay_num']         = $amount;  
    //         //     $dataobj['number']          = $number;  
    //         //     $dataobj['Begin_date']      = $begin_date;
    //         //     $dataobj['Expire_time']     = $expire_time;
    //         //     $invoice_no = $dataobj['number'];
                
    //         //     $url                        = "api/v1/product/recharge"; 
    //         //     $output                     = $this->PostFunction($url,$dataobj);
    //         //     $decode                     = json_decode($output,true);
    //         //     $data = [];
    //         //     if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //         //     {
    //         //         $data['access_token']       = $access_token;
    //         //         $data['user_name']          = $pay_details->user_name;
    //         //         $data['Expire_time']        = $pay_details->expire_time;
    //         //         $data['user_available']     = 0;
    //         //         $data['Arrears_days']       = 0;
                  
    //         //         $UpadteUser = $this->UpdateUser($data);
                  
    //         //         $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                      
    //         //         $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                    
    //         //         $rs = PaymentNew::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
    //         //         $rs1 = PaymentQuery::where('order_id', $request->order_no)->update(['admin_status'=>'1']);
                    
    //         //         PendingPayment::where('order_no', $order_no)->update(['status' => '1']);
                    
    //         //         return response()->json([
    //         //             'status'=>200,
    //         //             'message'=>'Order updated successfully.'
    //         //         ]);
    //         //     }else
    //         //     {
    //         //       return response()->json(['status'=>400,'message'=>$decode['message']]);
    //         //     }
    //         // }
    //     }else{
    //         return response()->json([
    //             'status'=>200,
    //             'message'=>'Order updated successfully.'
    //         ]);
    //     }
    // }
    
    public function UpdatePayment(Request $request)
    {
        $check_pay = PaymentQuery::where('order_id', $request->order_no)->where('admin_status', '1')->first();
        if(empty($check_pay)){
            $pay_details = PendingPayment::where('order_no', $request->order_no)->first();
            
            $user_name   = $pay_details->user_name;
            $order_no    = $pay_details->order_no;
            $payment_id  = $pay_details->payment_id;
            $amount      = $pay_details->amount;
            $number      = $pay_details->number;
            $begin_date  = $pay_details->begin_date;
            $expire_time = $pay_details->expire_time;
            
            if($pay_details->payment_id == '10'){
                
                $payment = WaveCallback::where('orderId', $order_no)->first();
                
                if($payment->status == 'PAYMENT_CONFIRMED'){
                
                    $access_token = $this->gettoken();
                    $dataobj["access_token"]    = $access_token;
                    $dataobj["user_name"]       = $user_name;
                    $dataobj["order_no"]        = $order_no;
                    $dataobj['pay_type_id']     = $payment_id; 
                    $dataobj['pay_num']         = $amount;  
                    $dataobj['number']          = $number;  
                    $dataobj['Begin_date']      = $begin_date;
                    $dataobj['Expire_time']     = $expire_time;
                    $invoice_no = $dataobj['number'];
                    
                    $url                        = "api/v1/product/recharge"; 
                    $output                     = $this->PostFunction($url,$dataobj);
                    $decode                     = json_decode($output,true);
                    $data = [];
                    if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
                    {
                        $data['access_token']       = $access_token;
                        $data['user_name']          = $pay_details->user_name;
                        $data['Expire_time']        = $pay_details->expire_time;
                        $data['user_available']     = 0;
                        $data['Arrears_days']       = 0;
                      
                        $UpadteUser = $this->UpdateUser($data);
                      
                        $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no,$payment->transactionId);
                          
                        $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                        
                        PendingPayment::where('order_no', $order_no)->update(['status' => '1']);
                        
                        return response()->json([
                            'status'=>200,
                            'message'=>'Order updated successfully.'
                        ]);
                    }else
                    {
                      return response()->json(['status'=>400,'message'=>$decode['message']]);
                    }
                }else{
                    
                    $this->failedpaymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$number,$oreder_id,$pay_details->amount,$pay_details->payment_id,$pay_details->created_at);
                    PendingPayment::where('order_no', $oreder_id)->update(['status' => '2']);
                    
                    return response()->json([
                        'status'=>400,
                        'message'=>'Payment not done.'
                    ]);
                }
                
            }elseif($pay_details->payment_id == '8'){
                
                $api_url          = $this->kbz_api_url.'/queryorder';
                $m_code           = $this->kbz_m_code;
                $appid            = $this->kbz_appid;
                $key              = $this->kbz_key;
                $trade_type       = $this->kbz_trade_type;
                
                $timestamp        = time();
                $nonce            = $this->generateRandomString();
                $method           = "kbz.payment.queryorder";
                $sign_type        = "SHA256";
                $version          = "3.0";
                $oreder_id        = $request->order_no;
                $c_time           = $timestamp;
                $nonce_str        = $nonce;
                
                $stringA = "appid=".$appid."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&timestamp=".$c_time."&version=".$version;
                
                $stringToSign = $stringA."&key=".$key;
                
                $signature = strtoupper(hash('sha256', $stringToSign));
                
                $kbz_params = [
                    "Request" => array(
                        "timestamp" => $c_time,
                        "nonce_str" => $nonce_str,
                        "method" => $method, 
                        "sign_type" => $sign_type, 
                        "sign" => $signature,
                        "version" => $version,
                        "biz_content" => array(
                            "appid" => $appid,
                            "merch_code" => $m_code, 
                            "merch_order_id" => $oreder_id
                        )
                    )
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                   "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($ch, CURLOPT_TIMEOUT, 80);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($ch);
                $response = json_decode($result, true);
               // dd($response['Response']['result']);
                
                if($response['Response']['result'] == 'SUCCESS' && $response['Response']['trade_status'] == 'PAY_SUCCESS'){
                    
                    $access_token = $this->gettoken();
                    $dataobj["access_token"]    = $access_token;
                    $dataobj["user_name"]       = $user_name;
                    $dataobj["order_no"]        = $oreder_id;
                    $dataobj['pay_type_id']     = $payment_id; 
                    $dataobj['pay_num']         = $amount;  
                    $dataobj['number']          = $number;  
                    $dataobj['Begin_date']      = $begin_date;
                    $dataobj['Expire_time']     = $expire_time;
                    $invoice_no = $dataobj['number'];
                    
                    $url                        = "api/v1/product/recharge"; 
                    $output                     = $this->PostFunction($url,$dataobj);
                    $decode                     = json_decode($output,true);
                    $data = [];
                    if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
                    {
                        $data['access_token']       = $access_token;
                        $data['user_name']          = $pay_details->user_name;
                        $data['Expire_time']        = $pay_details->expire_time;
                        $data['user_available']     = 0;
                        $data['Arrears_days']       = 0;
                      
                        $UpadteUser = $this->UpdateUser($data);
                      
                        $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no,$response['Response']['mm_order_id']);
                          
                        $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                        
                        PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                        
                        return response()->json([
                            'status'=>200,
                            'message'=>'Order updated successfully.'
                        ]);
                    }else
                    {
                      return response()->json(['status'=>400,'message'=>$decode['message']]);
                    }
                }else{
                    $this->failedpaymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$number,$oreder_id,$pay_details->amount,$pay_details->payment_id,$pay_details->created_at);
                    PendingPayment::where('order_no', $oreder_id)->update(['status' => '2']);
                    
                    return response()->json([
                        'status'=>400,
                        'message'=>'Payment not done.'
                    ]);
                }
                
            }elseif($pay_details->payment_id == '11'){
                
                $oreder_id        = $request->order_no;
                $oreder_ref       = $request->generateRefOrder;
        
                $api_url          = $this->api_url.'/checkstatus-webpayment.service';
                $ecommerce_id     = $this->ecommerce_id;
                
                $cb_params = [
                    "generateRefOrder" => $oreder_ref,
                    "ecommerceId" => $ecommerce_id,
                    "orderId" => $oreder_id
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                   "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cb_params));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($ch, CURLOPT_TIMEOUT, 80);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($ch);
                $response = json_decode($result, true);
                //dd($response);
                
                if(!empty($response['transactionStatus']) && $response['transactionStatus'] == 'S'){
                    
                    $access_token = $this->gettoken();
                    $dataobj["access_token"]    = $access_token;
                    $dataobj["user_name"]       = $user_name;
                    $dataobj["order_no"]        = $oreder_id;
                    $dataobj['pay_type_id']     = $payment_id; 
                    $dataobj['pay_num']         = $amount;  
                    $dataobj['number']          = $number;  
                    $dataobj['Begin_date']      = $begin_date;
                    $dataobj['Expire_time']     = $expire_time;
                    $invoice_no = $dataobj['number'];
                    
                    $url                        = "api/v1/product/recharge"; 
                    $output                     = $this->PostFunction($url,$dataobj);
                    $decode                     = json_decode($output,true);
                    $data = [];
                    if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
                    {
                        $data['access_token']       = $access_token;
                        $data['user_name']          = $pay_details->user_name;
                        $data['Expire_time']        = $pay_details->expire_time;
                        $data['user_available']     = 0;
                        $data['Arrears_days']       = 0;
                      
                        $UpadteUser = $this->UpdateUser($data);
                      
                        $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no,$response['transactionId']);
                          
                        $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                        
                        PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                        
                        return response()->json([
                            'status'=>200,
                            'message'=>'Order updated successfully.'
                        ]);
                    }else
                    {
                      return response()->json(['status'=>400,'message'=>$decode['message']]);
                    }
                }else{
                    
                    $this->failedpaymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$number,$oreder_id,$pay_details->amount,$pay_details->payment_id,$pay_details->created_at);
                    PendingPayment::where('order_no', $oreder_id)->update(['status' => '2']);
                    
                    return response()->json([
                        'status'=>400,
                        'message'=>'Payment not done.'
                    ]);
                }
                
            }
        }else{
            return response()->json([
                'status'=>200,
                'message'=>'Order updated successfully.'
            ]);
        }
    }
  
    //Get Paymemt Records // send button 
    public function paymentRecordsInsertWithDate($user_name,$begin,$phone,$expire,$invoice_no,$transaction_id)
    {
        $access_token = $this->gettoken(); //get_token
        $data         = "access_token={$access_token}&user_name={$user_name}";
        $url          = "api/v1/financial/payment-records?".$data; 
        $output       = $this->GetFunction($url);
        $decode       = json_decode($output,true);
        $value        = $decode['data']['0'];
 
        $SubComId = MbtBindUser::where('user_name',$user_name)->first(['sub_company','er_id']);

        $payment  = new PaymentQuery();
        $payment->sub_com_id        = $SubComId->sub_company;
        $payment->user_id           = $SubComId->er_id;
        $payment->payment_user_name = $value['user_name'];
        $payment->trans_date        = date('Y-m-d H:i:s',$value['create_at']); 
        $payment->total_amt         = $value['pay_num'];
        $payment->order_id          = $value['order_no']; 
        $payment->begin_date        = $begin;
        $payment->expire_date       = $expire; 
        $payment->admin_status      = 1;
        $payment->invoice_no        = $invoice_no;
        $payment->payment_method    = $value['pay_type_id'];
        $payment->package_id        = $value['package_id']; 
        $payment->product_id        = $value['product_id'];
        $payment->phone             = $phone;
        $payment->save();
        
        $payment_new  = new PaymentNew();
        $payment_new->sub_com_id        = $SubComId->sub_company;
        $payment_new->user_id           = $SubComId->er_id;
        $payment_new->payment_user_name = $value['user_name'];
        $payment_new->trans_date        = date('Y-m-d H:i:s',$value['create_at']); 
        $payment_new->total_amt         = $value['pay_num'];
        $payment_new->order_id          = $value['order_no']; 
        $payment_new->begin_date        = $begin;
        $payment_new->expire_date       = $expire;
        $payment_new->admin_status      = 1;
        $payment_new->invoice_no        = $invoice_no;
        $payment_new->payment_method    = $value['pay_type_id'];
        $payment_new->package_id        = $value['package_id']; 
        $payment_new->product_id        = $value['product_id'];
        $payment_new->phone             = $phone;
        $payment_new->save();
    }
    
    public function failedpaymentRecordsInsertWithDate($user_name,$begin,$phone,$expire,$invoice_no,$order_id,$total_amt,$payment_method,$created_at)
    {
        $SubComId = MbtBindUser::where('user_name',$user_name)->first(['sub_company','er_id']);

        $payment  = new PaymentQuery();
        $payment->sub_com_id        = $SubComId->sub_company;
        $payment->user_id           = $SubComId->er_id;
        $payment->payment_user_name = $user_name;
        $payment->trans_date        = $created_at; 
        $payment->total_amt         = $total_amt;
        $payment->order_id          = $order_id; 
        $payment->begin_date        = $begin;
        $payment->expire_date       = $expire; 
        $payment->admin_status      = 2;
        $payment->invoice_no        = $invoice_no;
        $payment->payment_method    = $payment_method;
        $payment->package_id        = 0; 
        $payment->product_id        = 0;
        $payment->phone             = $phone;
        $payment->transaction_id    = null;
        $payment->save();
        
        $payment_new  = new PaymentNew();
        $payment_new->sub_com_id        = $SubComId->sub_company;
        $payment_new->user_id           = $SubComId->er_id;
        $payment_new->payment_user_name = $user_name;
        $payment_new->trans_date        = $created_at; 
        $payment_new->total_amt         = $total_amt;
        $payment_new->order_id          = $order_id; 
        $payment_new->begin_date        = $begin;
        $payment_new->expire_date       = $expire;
        $payment_new->admin_status      = 2;
        $payment_new->invoice_no        = $invoice_no;
        $payment_new->payment_method    = $payment_method;
        $payment_new->package_id        = 0; 
        $payment_new->product_id        = 0;
        $payment_new->phone             = $phone;
        $payment_new->transaction_id    = null;
        $payment_new->save();
    }
  
  public function getpaymentrecords($user_name,$begin,$phone,$expire,$invoice_no)
  {
      $access_token = $this->gettoken(); //get_token
      $data         = "access_token={$access_token}&user_name={$user_name}";
      $url          = "api/v1/financial/payment-records?".$data; 
      $output       = $this->GetFunction($url);
      $decode       = json_decode($output,true);
      $value        = $decode['data']['0'];
      
    //   $order_no = $value['order_no'];
    $order_no = $value;
      
      return $order_no;
                  
           
  }
  public function get_language(Request $request)
    {   
        $language_id=$request->input('language_id');
        $validator = \Validator::make($request->all(),
         [
            'language_id' => 'required',
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
        $result= DB::table('users')->insert(['language_id' => $language_id]);
             
        return response()->json(['status'=>200,'message'=>'Get login id  Successfully!']);
        
    }
    //  user message api 
        public function user_message(Request $request)
            {   
                $user_id=$request->input('user_id');
                    $validator = \Validator::make($request->all(),
                     [
                        'user_id' => 'required',
                     ]);
                    if($validator->fails())
                    {
                        $response['response'] = $validator->messages();
                        return $response;
                    } 
                    
               $update = DB::table('chat')->where('reciever_userid',$user_id)->update(['mobile_read_status'=>1]);
               
                return response()->json(['status'=>200,'message'=>'Status change Successfully']);
            }
            
    // Logo image 
    
     public function logo_image(Request $request)
            {   
              $base_url           = "https://isp.mlbbshop.app/assets/front/app/";
              
              $mbt_profile= DB::table('settings')->select('header_logo')->get();
              $image = json_decode($mbt_profile, true);
              $header_image = $image['0']['header_logo'] ?? '';
            //   dd($header_image);
              
             
                
            return response()->json(['status'=>200,'Url'=>$base_url,'header_logo'=>$header_image]);
            
            }
            
    
    public function userdevice(Request $request)
    {   
        $check = UserDevice::where('device_id', $request->device_id)->first();
        if($check){
            $check_user = UserDevice::where('user_id', $request->user_id)->where('device_id', $request->device_id)->first();
            if($check_user){
                $device = UserDevice::where('user_id', $request->user_id)->where('device_id', $request->device_id)->update(['fcm_token'=>$request->fcm_token, 'language_id'=>$request->language_id]);
            }else{
                $device = UserDevice::where('device_id', $request->device_id)->update(['user_id'=>$request->user_id, 'fcm_token'=>$request->fcm_token, 'language_id'=>$request->language_id]);
            }
        }else{
            $device = UserDevice::create([
                'user_id' => $request->user_id,
                'device_id' => $request->device_id,
                'fcm_token' => $request->fcm_token,
                'language_id' => $request->language_id,
            ]);
        }
        
        return response()->json(['status'=>200,'message'=>'Device stored successfully.']);
    }
    
    public function mbtcbpay(Request $request)
    {   
        $oreder_id        = $request->orderId;
        $oreder_details   = $request->orderDetails;
        $amount           = $request->amount;
        $currency         = 'MMK';
        
        $api_url          = $this->api_url.'/request-payment-order.service';
        $auth_token       = $this->auth_token;
        $ecommerce_id     = $this->ecommerce_id;
        $sub_mer_id       = $this->sub_mer_id;
        $mer_id           = $this->mer_id;
        $transaction_type = $this->transaction_type;
        $notifyurl        = $this->notifyurl.'?order_id='.$request->orderId;
        
        $concat_structure = $auth_token.'&'.$ecommerce_id.'&'.$sub_mer_id.'&'.$oreder_id.'&'.$amount.'&'.$currency;
        $signature = hash('sha256', $concat_structure);
        
        $cb_params = [
            "authenToken" => $auth_token,
            "ecommerceId" => $ecommerce_id, 
            "transactionType" => $transaction_type,
            "subMerId" => $sub_mer_id,
            "notifyUrl" => $notifyurl, 
            "signature" => $signature,
            "orderId" => $oreder_id, 
            "orderDetails" => $oreder_details, 
            "amount" => $amount, 
            "currency" => $currency
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
           "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cb_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $response = json_decode($result, true);
        
        PendingPayment::where('order_no', $oreder_id)->update(['generateRefOrder' => $response['generateRefOrder']]);
        
        return response()->json([
            'status'=>200,
            'data'=>$response,
            'message'=>'Payment order requested successfully.'
        ]);
    }
    
    public function mbtcbpaystatus(Request $request)
    {   
        $oreder_id        = $request->orderId;
        $oreder_ref       = $request->generateRefOrder;

        $api_url          = $this->api_url.'/checkstatus-webpayment.service';
        $ecommerce_id     = $this->ecommerce_id;
        
        $cb_params = [
            "generateRefOrder" => $oreder_ref,
            "ecommerceId" => $ecommerce_id,
            "orderId" => $oreder_id
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
           "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cb_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $response = json_decode($result, true);
        //dd($response);
        
        if($response['transactionStatus'] == 'S'){
            $rs = PaymentNew::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
            $rs1 = PaymentQuery::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
        }
        
        return response()->json([
            'status'=>200,
            'data'=>$response,
            'message'=>'Payment Details.'
        ]);
    }
    
    // public function notify(Request $request) 
    // {
    //     if(!empty($_GET['order_id'])){
    //         $check = $_GET['order_id'];
    //         CborderDetail::create([
    //             'orders'=>$check
    //         ]);
            
    //         $update = CborderDetail::where('orders', $check)->update(['notify_data' => $request->all()]);
        
            
    //         // $pay_details = PendingPayment::where('order_no', $check)->first();
        
    //         // $user_name   = $pay_details->user_name;
    //         // $order_no    = $pay_details->order_no;
    //         // $payment_id  = $pay_details->payment_id;
    //         // $amount      = $pay_details->amount;
    //         // $number      = $pay_details->number;
    //         // $begin_date  = $pay_details->begin_date;
    //         // $expire_time = $pay_details->expire_time;
            
    //         // $access_token = $this->gettoken();
    //         // $dataobj["access_token"]    = $access_token;
    //         // $dataobj["user_name"]       = $user_name;
    //         // $dataobj["order_no"]        = $order_no;
    //         // $dataobj['pay_type_id']     = $payment_id; 
    //         // $dataobj['pay_num']         = $amount;  
    //         // $dataobj['number']          = $number;  
    //         // $dataobj['Begin_date']      = $begin_date;
    //         // $dataobj['Expire_time']     = $expire_time;
    //         // $invoice_no = $dataobj['number'];
            
    //         // $url                        = "api/v1/product/recharge"; 
    //         // $output                     = $this->PostFunction($url,$dataobj);
    //         // $decode                     = json_decode($output,true);
    //         // $data = [];
    //         // if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //         // {
    //         //     $data['access_token']       = $access_token;
    //         //     $data['user_name']          = $pay_details->user_name;
    //         //     $data['Expire_time']        = $pay_details->expire_time;
    //         //     $data['user_available']     = 0;
    //         //     $data['Arrears_days']       = 0;
              
    //         //     $UpadteUser = $this->UpdateUser($data);
              
    //         //     $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                  
    //         //     $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                
    //         //     $rs = PaymentNew::where('order_id', $request->order_id)->update(['admin_status'=>'1']);
    //         //     $rs1 = PaymentQuery::where('order_id', $request->order_id)->update(['admin_status'=>'1']);
            
    //         //     PendingPayment::where('order_no', $order_no)->update(['status' => '1']);
                
    //         //     return response()->json([
    //         //         'status'=>200,
    //         //         'message'=>'Payment done successfully.'
    //         //     ]);
    //         // }else
    //         // {
    //         //   return response()->json(['status'=>400,'message'=>$decode['message']]);
    //         // }
            
            
    //         return response()->json([
    //             'responseCode'=>000,
    //             'responseMessage'=>'Operation Success'
    //         ]);
    //     }else{
    //         // $not_data = $request->all();
            
    //         // $notify_params = [
    //         //     "Request" => [
    //         //         "notify_time" => $not_data['Request']['notify_time'],
    //         //         "merch_code" => $not_data['Request']['merch_code'],
    //         //     ]
    //         // ];
            
    //         // $ch = curl_init();
    //         // curl_setopt($ch, CURLOPT_URL, 'https://telco.mbt.com.mm/api/notify');
    //         // curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //         //   "Content-Type: application/json"
    //         // ]);
    //         // curl_setopt($ch, CURLOPT_POST, 1);
    //         // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notify_params));
    //         // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //         // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    //         // curl_setopt($ch, CURLOPT_TIMEOUT, 80);
    //         // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //         // $result = curl_exec($ch);
            
    //         // if ($result === false)
    //         // {
    //         //   echo 'Curl error: ' . curl_error($ch);
    //         // }
    //         // else
    //         // {
    //         //     dd($result);
    //         // }
            
    //         return 'success';
    //     }
    // }
    
    public function notify(Request $request) 
    {
        if(!empty($_GET['order_id'])){
            $check = $_GET['order_id'];
            CborderDetail::create([
                'orders'=>$check
            ]);
            
            $update = CborderDetail::where('orders', $check)->update(['notify_data' => $request->all()]);
            
            $pay_details = PendingPayment::where('order_no', $check)->where('status', '!=', '1')->first();
            if(!empty($pay_details)){
                $user_name   = $pay_details->user_name;
                $order_no    = $pay_details->order_no;
                $payment_id  = $pay_details->payment_id;
                $amount      = $pay_details->amount;
                $number      = $pay_details->number;
                $begin_date  = $pay_details->begin_date;
                $expire_time = $pay_details->expire_time;
                $phone       = $pay_details->phone;
                
                $access_token = $this->gettoken();
                $dataobj["access_token"]    = $access_token;
                $dataobj["user_name"]       = $user_name;
                $dataobj["order_no"]        = $order_no;
                $dataobj['pay_type_id']     = $payment_id; 
                $dataobj['pay_num']         = $amount;  
                $dataobj['number']          = $number;  
                $dataobj['Begin_date']      = $begin_date;
                $dataobj['Expire_time']     = $expire_time;
                $invoice_no = $dataobj['number'];
                
                $url                        = "api/v1/product/recharge"; 
                $output                     = $this->PostFunction($url,$dataobj);
                $decode                     = json_decode($output,true);
                $data = [];
                if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
                {
                    $data['access_token']       = $access_token;
                    $data['user_name']          = $pay_details->user_name;
                    $data['Expire_time']        = $pay_details->expire_time;
                    $data['user_available']     = 0;
                    $data['Arrears_days']       = 0;
                    
                    $datan         = "access_token={$access_token}&user_name={$user_name}";
                    $url          = "api/v1/financial/payment-records?".$datan; 
                    $output       = $this->GetFunction($url);
                    $decode       = json_decode($output,true);
                    $value        = $decode['data']['0'];
             
                    $SubComId = MbtBindUser::where('user_name',$user_name)->first(['sub_company','er_id']);
                    
                    $UpadteUser = $this->UpdateUser($data);
                    
                    $pay_query = PaymentQuery::where('order_id', $check)->first();
                    if(empty($pay_query)){
                        $payment  = new PaymentQuery();
                        $payment->sub_com_id        = $SubComId->sub_company;
                        $payment->user_id           = $SubComId->er_id;
                        $payment->payment_user_name = $value['user_name'];
                        $payment->trans_date        = date('Y-m-d H:i:s',$value['create_at']); 
                        $payment->total_amt         = $value['pay_num'];
                        $payment->order_id          = $value['order_no']; 
                        $payment->begin_date        = $begin_date;
                        $payment->expire_date       = $expire_time; 
                        $payment->admin_status      = 1;
                        $payment->invoice_no        = $invoice_no;
                        $payment->payment_method    = $value['pay_type_id'];
                        $payment->package_id        = $value['package_id']; 
                        $payment->product_id        = $value['product_id'];
                        $payment->phone             = $phone;
                        $payment->save();
                    }else{
                        PaymentQuery::where('order_id', $check)->update(['admin_status'=>'1']);
                    }
                    
                    $pay_new = PaymentNew::where('order_id', $check)->first();
                    if(empty($pay_new)){
                        $payment_new  = new PaymentNew();
                        $payment_new->sub_com_id        = $SubComId->sub_company;
                        $payment_new->user_id           = $SubComId->er_id;
                        $payment_new->payment_user_name = $value['user_name'];
                        $payment_new->trans_date        = date('Y-m-d H:i:s',$value['create_at']); 
                        $payment_new->total_amt         = $value['pay_num'];
                        $payment_new->order_id          = $value['order_no']; 
                        $payment_new->begin_date        = $begin_date;
                        $payment_new->expire_date       = $expire_time;
                        $payment_new->admin_status      = 1;
                        $payment_new->invoice_no        = $invoice_no;
                        $payment_new->payment_method    = $value['pay_type_id'];
                        $payment_new->package_id        = $value['package_id']; 
                        $payment_new->product_id        = $value['product_id'];
                        $payment_new->phone             = $phone;
                        $payment_new->save();
                    }else{
                        PaymentNew::where('order_id', $check)->update(['admin_status'=>'1']);
                    }
                    
                    PendingPayment::where('order_no', $check)->update(['status' => '1']);
                    
                    return response()->json([
                        'status'=>200,
                        'message'=>'Payment done successfully.'
                    ]);
                }else
                {
                  return response()->json(['status'=>400,'message'=>$decode['message']]);
                }
            }
            
            return response()->json([
                'responseCode'=>000,
                'responseMessage'=>'Operation Success'
            ]);
        }else{
            // $not_data = $request->all();
            
            // $notify_params = [
            //     "Request" => [
            //         "notify_time" => $not_data['Request']['notify_time'],
            //         "merch_code" => $not_data['Request']['merch_code'],
            //     ]
            // ];
            
            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, 'https://telco.mbt.com.mm/api/notify');
            // curl_setopt($ch, CURLOPT_HTTPHEADER, [
            //   "Content-Type: application/json"
            // ]);
            // curl_setopt($ch, CURLOPT_POST, 1);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notify_params));
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            // curl_setopt($ch, CURLOPT_TIMEOUT, 80);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // $result = curl_exec($ch);
            
            // if ($result === false)
            // {
            //   echo 'Curl error: ' . curl_error($ch);
            // }
            // else
            // {
            //     dd($result);
            // }
            
            return 'success';
        }
    }
    
    public function referer_url() {
        $data['details'] = BankSetting::where('id', '1')->first();   
        return view('front.referer', $data);
    }
    
    public function return_url() {
        
        return view('front.success');
        // return response()->json([
        //     'status'=>200,
        //     'message'=>'This is return page.'
        // ]);
    }
    
    public function wave_return_url() {
        
        return view('front.wave_success');
        // return response()->json([
        //     'status'=>200,
        //     'message'=>'This is return page.'
        // ]);
    }
    
    public function callback_url(Request $request) {
        $request->all();
        $rs = CborderDetail::create([
            'orders'=>$request->all()
        ]);
        
        return response()->json([
            'responseCode'=>000,
            'responseMessage'=>'Operation Success'
        ]);
    }
    
    public function cbredirect(Request $request)
    {
        $key_ref = $request->key_ref;
        
        $url = $this->cb_redirect."?keyreference=".$key_ref;
        
        return response()->json([
            'responseCode'=>000,
            'cb_url'=>$url,
            'responseMessage'=>'Operation Success'
        ]);
    }
    
    public function mbtkbzpay(Request $request)
    {  
        $api_url          = $this->kbz_api_url.'/precreate';
        $m_code           = $this->kbz_m_code;
        $appid            = $this->kbz_appid;
        $key              = $this->kbz_key;
        $trade_type       = $this->kbz_trade_type;
        
        $timestamp        = time();
        $method           = "kbz.payment.precreate";
        $notifyurl        = $this->kbz_notifyurl.'?order_id='.$request->orderId;
        $nonce            = $this->generateRandomString();
        $sign_type        = "SHA256";
        $version          = $this->kbz_version;
        $oreder_id        = $request->orderId;
        $oreder_details   = $request->orderDetails;
        $amount           = $request->amount;
        $currency         = 'MMK';
        $timeout          = "120m";
        $callback         = "title%3dipho";
        $c_time           = $timestamp;
        $nonce_str        = $nonce;
        $pay_details = PendingPayment::where('order_no', $oreder_id)->first();
        $business_param   = $pay_details->user_name;
        
        $stringA = "appid=".$appid."&business_param=".$business_param."&callback_info=".$callback."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&notify_url=".$notifyurl."&timeout_express=".$timeout."&timestamp=".$c_time."&title=".$oreder_details."&total_amount=".$amount."&trade_type=".$trade_type."&trans_currency=".$currency."&version=".$version;
        $stringToSign = $stringA."&key=".$key;
        $signature = strtoupper(hash('sha256', $stringToSign));

        $kbz_params = [
            "Request" => array(
                "timestamp" => $c_time,
                "method" => $method, 
                "notify_url" => $notifyurl,
                "nonce_str" => $nonce_str,
                "sign_type" => $sign_type, 
                "sign" => $signature,
                "version" => $version,
                "biz_content" => array(
                    "merch_order_id" => $oreder_id, 
                    "merch_code" => $m_code, 
                    "appid" => $appid,
                    "trade_type" => $trade_type,
                    "title" => $oreder_details,
                    "total_amount" => $amount,
                    "trans_currency" => $currency,
                    "timeout_express" => $timeout,
                    "callback_info" => $callback,
                    "business_param" => $business_param
                )
            )
        ];
        
        //dd(json_encode($kbz_params));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $response = json_decode($result, true);
        //dd($response);
        
        return response()->json([
            'status'=>200,
            'data'=>$response,
            //'redirect_url'=>"https://static.kbzpay.com/pgw/uat/pwa/#/?appid=".$appid."&merch_code=".$m_code."&nonce_str=".$nonce_str."&prepay_id=".$response['Response']['prepay_id']."&timestamp=".$c_time."&sign=".$response['Response']['sign'],
            //'redirect_url1'=>"https://static.kbzpay.com/pgw/uat/pwa/#/?appid=".$appid."&merch_code=".$m_code."&nonce_str=".$nonce_str."&prepay_id=".$response['Response']['prepay_id']."&timestamp=".$c_time."&sign=".$signature,
            //'signature'=>$signature,
            'message'=>'Payment order requested successfully.'
        ]);
    }
    
    // public function mbtkbzpay(Request $request)
    // {  
    //     $api_url          = $this->kbz_api_url.'/precreate';
    //     $m_code           = $this->kbz_m_code;
    //     $appid            = $this->kbz_appid;
    //     $key              = $this->kbz_key;
    //     $trade_type       = $this->kbz_trade_type;
        
    //     // $business_url = $request->mbt_id;
    //     // $business_url = urldecode($business_url);  
    //     // $business_url = str_replace(' ', '%20', $business_url); 
    //     // $business_url = str_replace('&', '%26', $business_url); 
    //     // "business_param" => $business_url,
        
    //     $timestamp        = time();
    //     $method           = "kbz.payment.precreate";
    //     $notifyurl        = $this->kbz_notifyurl.'?order_id='.$request->orderId;
    //     $nonce            = $this->generateRandomString();
    //     $sign_type        = "SHA256";
    //     $version          = $this->kbz_version;
    //     $oreder_id        = $request->orderId;
    //     $oreder_details   = $request->orderDetails;
    //     $amount           = $request->amount;
    //     $currency         = 'MMK';
    //     $timeout          = "120m";
    //     $callback         = "title%3dipho";
    //     $c_time           = $timestamp;
    //     $nonce_str        = $nonce;
        
    //     $stringA = "appid=".$appid."&callback_info=".$callback."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&notify_url=".$notifyurl."&timeout_express=".$timeout."&timestamp=".$c_time."&title=".$oreder_details."&total_amount=".$amount."&trade_type=".$trade_type."&trans_currency=".$currency."&version=".$version;
    //     $stringToSign = $stringA."&key=".$key;
        
    //     $signature = strtoupper(hash('sha256', $stringToSign));
        
    //     $kbz_params = [
    //         "Request" => array(
    //             "timestamp" => $c_time,
    //             "method" => $method, 
    //             "notify_url" => $notifyurl,
    //             "nonce_str" => $nonce_str,
    //             "sign_type" => $sign_type, 
    //             "sign" => $signature,
    //             "version" => $version,
    //             "biz_content" => array(
    //                 "merch_order_id" => $oreder_id, 
    //                 "merch_code" => $m_code, 
    //                 "appid" => $appid,
    //                 "trade_type" => $trade_type,
    //                 "title" => $oreder_details,
    //                 "total_amount" => $amount,
    //                 "trans_currency" => $currency,
    //                 "timeout_express" => $timeout,
    //                 "callback_info" => $callback,
    //             )
    //         )
    //     ];
        
    //     //dd(json_encode($kbz_params));
        
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $api_url);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //       "Content-Type: application/json"
    //     ]);
    //     curl_setopt($ch, CURLOPT_POST, 1);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    //     curl_setopt($ch, CURLOPT_TIMEOUT, 80);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     $result = curl_exec($ch);
    //     $response = json_decode($result, true);
    //     //dd($response);
        
    //     return response()->json([
    //         'status'=>200,
    //         'data'=>$response,
    //         //'redirect_url'=>"https://static.kbzpay.com/pgw/uat/pwa/#/?appid=".$appid."&merch_code=".$m_code."&nonce_str=".$nonce_str."&prepay_id=".$response['Response']['prepay_id']."&timestamp=".$c_time."&sign=".$response['Response']['sign'],
    //         //'redirect_url1'=>"https://static.kbzpay.com/pgw/uat/pwa/#/?appid=".$appid."&merch_code=".$m_code."&nonce_str=".$nonce_str."&prepay_id=".$response['Response']['prepay_id']."&timestamp=".$c_time."&sign=".$signature,
    //         //'signature'=>$signature,
    //         'message'=>'Payment order requested successfully.'
    //     ]);
    // }
    
    function generateRandomString($length = 32) {   
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    function generateRandomStringKBZ($length = 16) {   
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    public function mbtkbzpaystatus(Request $request)
    {  
        $api_url          = $this->kbz_api_url.'/queryorder';
        $m_code           = $this->kbz_m_code;
        $appid            = $this->kbz_appid;
        $key              = $this->kbz_key;
        $trade_type       = $this->kbz_trade_type;
        
        $timestamp        = time();
        $nonce            = $this->generateRandomString();
        $method           = "kbz.payment.queryorder";
        $sign_type        = "SHA256";
        $version          = "3.0";
        $oreder_id        = $request->orderId;
        $c_time           = $timestamp;
        $nonce_str        = $nonce;
        
        
        $stringA = "appid=".$appid."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&timestamp=".$c_time."&version=".$version;
        
        $stringToSign = $stringA."&key=".$key;
        
        $signature = strtoupper(hash('sha256', $stringToSign));
        
        $kbz_params = [
            "Request" => array(
                "timestamp" => $c_time,
                "nonce_str" => $nonce_str,
                "method" => $method, 
                "sign_type" => $sign_type, 
                "sign" => $signature,
                "version" => $version,
                "biz_content" => array(
                    "appid" => $appid,
                    "merch_code" => $m_code, 
                    "merch_order_id" => $oreder_id
                )
            )
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
           "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $response = json_decode($result, true);
       // dd($response['Response']['result']);
        
        if($response['Response']['result'] == 'SUCCESS' && $response['Response']['trade_status'] == 'PAY_SUCCESS'){
            $rs = PaymentNew::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
            $rs1 = PaymentQuery::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
            $rs2 = PendingPayment::where('order_no', $oreder_id)->update(['status'=>'1']);
        }
        
        return response()->json([
            'status'=>200,
            'data'=>$response,
            'message'=>'Payment Details.'
        ]);
    }
    
    public function kbzredirect(Request $request)
    {
        $key_ref          = $request->key_ref;
        $api_url          = $this->kbz_redirecct;
        $m_code           = $this->kbz_m_code;
        $appid            = $this->kbz_appid;
        $key              = $this->kbz_key;
        
        $timestamp        = time();
        $nonce            = $this->generateRandomString();
        $prepay_id        = $request->prepay_id;
        $c_time           = $timestamp;
        $nonce_str        = $nonce;
        
        $stringA = "appid=".$appid."&merch_code=".$m_code."&nonce_str=".$nonce_str."&prepay_id=".$prepay_id."&timestamp=".$c_time;
        $stringToSign = $stringA."&key=".$key;
        
        $signature = hash('sha256', $stringToSign);
        
        $url = $api_url."appid=".$appid."&merch_code=".$m_code."&nonce_str=".$nonce_str."&prepay_id=".$prepay_id."&timestamp=".$c_time."&sign=".$signature;
        
        return response()->json([
            'responseCode'=>000,
            'kbz_url'=>$url,
            'responseMessage'=>'Operation Success'
        ]);
    }
    
    public function mbtkbzrefundstatus(Request $request)
    {  
        $api_url          = 'http://api.kbzpay.com/payment/gateway/uat/queryrefund';
        
        $timestamp        = time();
        $nonce            = $this->generateRandomString();
        $method           = "kbz.payment.queryrefund";
        $sign_type        = "SHA256";
        $version          = "3.0";
        $oreder_id        = $request->orderId;
        $m_code           = "200196";
        $appid            = "kpa69153913b5847509177159d8a29f7";
        
        $c_time           = $timestamp;
        $nonce_str        = $nonce;
        $key              = "myanmarbroadband123";
        
        $stringA = "appid=".$appid."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&timestamp=".$c_time."&version=".$version;
        
        $stringToSign = $stringA."&key=".$key;
        
        $signature = strtoupper(hash('sha256', $stringToSign));
        
        $kbz_params = [
            "Request" => array(
                "timestamp" => $c_time,
                "nonce_str" => $nonce_str,
                "method" => $method, 
                "sign_type" => $sign_type, 
                "sign" => $signature,
                "version" => $version,
                "biz_content" => array(
                    "appid" => $appid,
                    "merch_code" => $m_code, 
                    "merch_order_id" => $oreder_id
                )
            )
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
           "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $response = json_decode($result, true);
        //dd($response);
        
        return response()->json([
            'status'=>200,
            'data'=>$response,
            'message'=>'Payment Details.'
        ]);
    }
    
    public function mbtkbzclose(Request $request)
    {  
        $api_url          = 'http://api.kbzpay.com/payment/gateway/uat/closeorder';
        
        $timestamp        = time();
        $nonce            = $this->generateRandomString();
        $method           = "kbz.payment.closeorder";
        $sign_type        = "SHA256";
        $version          = "3.0";
        $oreder_id        = $request->orderId;
        $m_code           = "200196";
        $appid            = "kpa69153913b5847509177159d8a29f7";
        
        $c_time           = $timestamp;
        $nonce_str        = $nonce;
        $key              = "myanmarbroadband123";
        
        $stringA = "appid=".$appid."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&timestamp=".$c_time."&version=".$version;
        
        $stringToSign = $stringA."&key=".$key;
        
        $signature = strtoupper(hash('sha256', $stringToSign));
        
        $kbz_params = [
            "Request" => array(
                "timestamp" => $c_time,
                "nonce_str" => $nonce_str,
                "method" => $method, 
                "sign_type" => $sign_type, 
                "sign" => $signature,
                "version" => $version,
                "biz_content" => array(
                    "appid" => $appid,
                    "merch_code" => $m_code, 
                    "merch_order_id" => $oreder_id
                )
            )
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
           "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $response = json_decode($result, true);
        //dd($response);
        
        return response()->json([
            'status'=>200,
            'data'=>$response,
            'message'=>'Payment Details.'
        ]);
    }
    
    public function mbtkbzrefund(Request $request)
    {  
        $api_url          = 'https://api.kbzpay.com:18008/payment/gateway/uat/refund';
        
        $timestamp        = time();
        $nonce            = $this->generateRandomString();
        $method           = "kbz.payment.refund";
        $sign_type        = "SHA256";
        $version          = "1.0";
        $oreder_id        = $request->orderId;
        $m_code           = "200196";
        $appid            = "kpa69153913b5847509177159d8a29f7";
        $request_no       = $request->request_number;
        $reason           = $request->return_reason;
        $refund_amount    = $request->refund_amount;
        
        $c_time           = $timestamp;
        $nonce_str        = $nonce;
        $key              = "myanmarbroadband123";
        
        $stringA = "appid=".$appid."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&timestamp=".$c_time."&version=".$version."&refund_request_no=".$request_no."&refund_reason=".$reason."&refund_amount=".$refund_amount;
        
        $stringToSign = $stringA."&key=".$key;
        
        $signature = strtoupper(hash('sha256', $stringToSign));
        
        $kbz_params = [
            "Request" => array(
                "timestamp" => $c_time,
                "nonce_str" => $nonce_str,
                "method" => $method, 
                "sign_type" => $sign_type, 
                "sign" => $signature,
                "version" => $version,
                "biz_content" => array(
                    "appid" => $appid,
                    "merch_order_id" => $oreder_id,
                    "merch_code" => $m_code, 
                    "refund_request_no" => $request_no,
                    "refund_reason" => $reason,
                    "refund_amount" => $refund_amount,
                )
            )
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
           "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
        $result = curl_exec($ch);
        $response = json_decode($result, true);
        dd($result);
        
        return response()->json([
            'status'=>200,
            'data'=>$response,
            'message'=>'Payment Details.'
        ]);
    }
    
    // public function check_payment(Request $request)
    // {
    //     $user_name = $request->username;
    //     $orders = PendingPayment::where('user_name', $user_name)->where('status', '0')->where('created_at', '>', '2024-03-11 12:20:05')->get();
        
    //     foreach($orders as $order){
    //         $oreder_id   = $order->order_no;
    //         $payment_id  = $order->payment_id;
    //         $amount      = $order->amount;
    //         $number      = $order->number;
    //         $begin_date  = $order->begin_date;
    //         $expire_time = $order->expire_time;
    //         $phone       = $order->phone;
    //         $oreder_ref  = $order->generateRefOrder;
            
    //         if($order->payment_id == '8'){
                
    //             $api_url          = $this->kbz_api_url.'/queryorder';
    //             $m_code           = $this->kbz_m_code;
    //             $appid            = $this->kbz_appid;
    //             $key              = $this->kbz_key;
    //             $trade_type       = $this->kbz_trade_type;
                
    //             $timestamp        = time();
    //             $nonce            = $this->generateRandomString();
    //             $method           = "kbz.payment.queryorder";
    //             $sign_type        = "SHA256";
    //             $version          = "3.0";
    //             $c_time           = $timestamp;
    //             $nonce_str        = $nonce;
                
    //             $stringA = "appid=".$appid."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&timestamp=".$c_time."&version=".$version;
                
    //             $stringToSign = $stringA."&key=".$key;
                
    //             $signature = strtoupper(hash('sha256', $stringToSign));
                
    //             $kbz_params = [
    //                 "Request" => array(
    //                     "timestamp" => $c_time,
    //                     "nonce_str" => $nonce_str,
    //                     "method" => $method, 
    //                     "sign_type" => $sign_type, 
    //                     "sign" => $signature,
    //                     "version" => $version,
    //                     "biz_content" => array(
    //                         "appid" => $appid,
    //                         "merch_code" => $m_code, 
    //                         "merch_order_id" => $oreder_id
    //                     )
    //                 )
    //             ];
                
    //             $ch = curl_init();
    //             curl_setopt($ch, CURLOPT_URL, $api_url);
    //             curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //               "Content-Type: application/json"
    //             ]);
    //             curl_setopt($ch, CURLOPT_POST, 1);
    //             curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
    //             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //             curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    //             curl_setopt($ch, CURLOPT_TIMEOUT, 80);
    //             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //             $result = curl_exec($ch);
    //             $response = json_decode($result, true);
                
    //             if($response['Response']['result'] == 'SUCCESS' && $response['Response']['trade_status'] == 'PAY_SUCCESS'){
    //                 $rs = PaymentNew::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
    //                 $rs1 = PaymentQuery::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
                    
    //                 $access_token = $this->gettoken();
    //                 $dataobj["access_token"]    = $access_token;
    //                 $dataobj["user_name"]       = $user_name;
    //                 $dataobj["order_no"]        = $oreder_id;
    //                 $dataobj['pay_type_id']     = $payment_id; 
    //                 $dataobj['pay_num']         = $amount;  
    //                 $dataobj['number']          = $number;  
    //                 $dataobj['Begin_date']      = $begin_date;
    //                 $dataobj['Expire_time']     = $expire_time;
                    
    //                 $invoice_no = $dataobj['number'];
                    
    //                 $url                        = "api/v1/product/recharge"; 
    //                 $output                     = $this->PostFunction($url,$dataobj);
    //                 $decode                     = json_decode($output,true);
    //                 $data = [];
    //                 if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //                 {
    //                     $data['access_token']       = $access_token;
    //                     $data['user_name']          = $user_name;
    //                     $data['Expire_time']        = $expire_time;
    //                     $data['user_available']     = 0;
    //                     $data['Arrears_days']       = 0;
                      
    //                     $UpadteUser = $this->UpdateUser($data);
                      
    //                     $this->paymentRecordsInsertWithDate($user_name,$begin_date,$phone,$expire_time,$invoice_no);
                          
    //                     $rs_mbt = $this->getpaymentrecords($user_name,$begin_date,$phone,$expire_time,$invoice_no);
                        
    //                     PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                        
    //                     $rs = PaymentNew::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
    //                     $rs1 = PaymentQuery::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
                        
    //                     return response()->json([
    //                         'status'=>200,
    //                         'message'=>'Order updated successfully.'
    //                     ]);
    //                 }else
    //                 {
    //                   return response()->json(['status'=>400,'message'=>$decode['message']]);
    //                 }
    //             }
    //         }elseif($order->payment_id == '11'){
                
    //             $api_url          = $this->api_url.'/checkstatus-webpayment.service';
    //             $ecommerce_id     = $this->ecommerce_id;
                
    //             $cb_params = [
    //                 "generateRefOrder" => $oreder_ref,
    //                 "ecommerceId" => $ecommerce_id,
    //                 "orderId" => $oreder_id
    //             ];
                
    //             $ch = curl_init();
    //             curl_setopt($ch, CURLOPT_URL, $api_url);
    //             curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //               "Content-Type: application/json"
    //             ]);
    //             curl_setopt($ch, CURLOPT_POST, 1);
    //             curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cb_params));
    //             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //             curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    //             curl_setopt($ch, CURLOPT_TIMEOUT, 80);
    //             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //             $result = curl_exec($ch);
    //             $response = json_decode($result, true);
                
    //             if(!empty($response['transactionStatus']) && $response['transactionStatus'] == 'S'){
    //                 $rs = PaymentNew::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
    //                 $rs1 = PaymentQuery::where('order_id', $oreder_id)->update(['admin_status'=>'1']);
                    
    //                 $access_token = $this->gettoken();
    //                 $dataobj["access_token"]    = $access_token;
    //                 $dataobj["user_name"]       = $user_name;
    //                 $dataobj["order_no"]        = $oreder_id;
    //                 $dataobj['pay_type_id']     = $payment_id; 
    //                 $dataobj['pay_num']         = $amount;  
    //                 $dataobj['number']          = $number;  
    //                 $dataobj['Begin_date']      = $begin_date;
    //                 $dataobj['Expire_time']     = $expire_time;
    //                 $invoice_no = $dataobj['number'];
                    
    //                 $url                        = "api/v1/product/recharge"; 
    //                 $output                     = $this->PostFunction($url,$dataobj);
    //                 $decode                     = json_decode($output,true);
    //                 $data = [];
    //                 if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
    //                 {
    //                     $data['access_token']       = $access_token;
    //                     $data['user_name']          = $pay_details->user_name;
    //                     $data['Expire_time']        = $pay_details->expire_time;
    //                     $data['user_available']     = 0;
    //                     $data['Arrears_days']       = 0;
                      
    //                     $UpadteUser = $this->UpdateUser($data);
                      
    //                     $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                          
    //                     $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                        
    //                     PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                        
    //                     return response()->json([
    //                         'status'=>200,
    //                         'message'=>'Order updated successfully.'
    //                     ]);
    //                 }else
    //                 {
    //                   return response()->json(['status'=>400,'message'=>$decode['message']]);
    //                 }
    //             }
    //         }
    //     }
        
    //     return response()->json([
    //         'status'=>200,
    //         'data'=>null,
    //         'message'=>'Payment Details.'
    //     ]);
    // }
    
    public function check_payment(Request $request)
    {
        $user_name = $request->username;
        $orders = PendingPayment::where('user_name', $user_name)->where('status', '0')->whereDate('created_at', '>', '2024-02-29 12:33:19')->get();
        
        foreach($orders as $order){
            $oreder_id   = $order->order_no;
            $payment_id  = $order->payment_id;
            $amount      = $order->amount;
            $number      = $order->number;
            $begin_date  = $order->begin_date;
            $expire_time = $order->expire_time;
            $phone       = $order->phone;
            $oreder_ref  = $order->generateRefOrder;
            
            if($order->payment_id == '8'){
                
                $api_url          = $this->kbz_api_url.'/queryorder';
                $m_code           = $this->kbz_m_code;
                $appid            = $this->kbz_appid;
                $key              = $this->kbz_key;
                $trade_type       = $this->kbz_trade_type;
                
                $timestamp        = time();
                $nonce            = $this->generateRandomString();
                $method           = "kbz.payment.queryorder";
                $sign_type        = "SHA256";
                $version          = "3.0";
                $c_time           = $timestamp;
                $nonce_str        = $nonce;
                
                $stringA = "appid=".$appid."&merch_code=".$m_code."&merch_order_id=".$oreder_id."&method=".$method."&nonce_str=".$nonce_str."&timestamp=".$c_time."&version=".$version;
                
                $stringToSign = $stringA."&key=".$key;
                
                $signature = strtoupper(hash('sha256', $stringToSign));
                
                $kbz_params = [
                    "Request" => array(
                        "timestamp" => $c_time,
                        "nonce_str" => $nonce_str,
                        "method" => $method, 
                        "sign_type" => $sign_type, 
                        "sign" => $signature,
                        "version" => $version,
                        "biz_content" => array(
                            "appid" => $appid,
                            "merch_code" => $m_code, 
                            "merch_order_id" => $oreder_id
                        )
                    )
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                   "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($kbz_params));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($ch, CURLOPT_TIMEOUT, 80);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($ch);
                $response = json_decode($result, true);
                
                if($response['Response']['result'] == 'SUCCESS' && $response['Response']['trade_status'] == 'PAY_SUCCESS'){
                    
                    $access_token = $this->gettoken();
                    $dataobj["access_token"]    = $access_token;
                    $dataobj["user_name"]       = $user_name;
                    $dataobj["order_no"]        = $oreder_id;
                    $dataobj['pay_type_id']     = $payment_id; 
                    $dataobj['pay_num']         = $amount;  
                    $dataobj['number']          = $number;  
                    $dataobj['Begin_date']      = $begin_date;
                    $dataobj['Expire_time']     = $expire_time;
                    
                    $invoice_no = $dataobj['number'];
                    
                    $url                        = "api/v1/product/recharge"; 
                    $output                     = $this->PostFunction($url,$dataobj);
                    $decode                     = json_decode($output,true);
                    $data = [];
                    if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
                    {
                        $data['access_token']       = $access_token;
                        $data['user_name']          = $user_name;
                        $data['Expire_time']        = $expire_time;
                        $data['user_available']     = 0;
                        $data['Arrears_days']       = 0;
                      
                        $UpadteUser = $this->UpdateUser($data);
                      
                        $this->paymentRecordsInsertWithDate($user_name,$begin_date,$phone,$expire_time,$invoice_no,$response['Response']['mm_order_id']);
                          
                        $rs_mbt = $this->getpaymentrecords($user_name,$begin_date,$phone,$expire_time,$invoice_no);
                        
                        PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                        
                        return response()->json([
                            'status'=>200,
                            'message'=>'Order updated successfully.'
                        ]);
                    }else
                    {
                      return response()->json(['status'=>400,'message'=>$decode['message']]);
                    }
                }else{
                    $this->failedpaymentRecordsInsertWithDate($order->user_name,$order->begin_date,$order->phone,$order->expire_time,$number,$oreder_id,$order->amount,$order->payment_id,$order->created_at);
                    PendingPayment::where('order_no', $oreder_id)->update(['status' => '2']);
                }
            }elseif($order->payment_id == '11'){
                
                $api_url          = $this->api_url.'/checkstatus-webpayment.service';
                $ecommerce_id     = $this->ecommerce_id;
                
                $cb_params = [
                    "generateRefOrder" => $oreder_ref,
                    "ecommerceId" => $ecommerce_id,
                    "orderId" => $oreder_id
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                   "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cb_params));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($ch, CURLOPT_TIMEOUT, 80);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($ch);
                $response = json_decode($result, true);
                
                if(!empty($response['transactionStatus']) && $response['transactionStatus'] == 'S'){
                    
                    $access_token = $this->gettoken();
                    $dataobj["access_token"]    = $access_token;
                    $dataobj["user_name"]       = $user_name;
                    $dataobj["order_no"]        = $oreder_id;
                    $dataobj['pay_type_id']     = $payment_id; 
                    $dataobj['pay_num']         = $amount;  
                    $dataobj['number']          = $number;  
                    $dataobj['Begin_date']      = $begin_date;
                    $dataobj['Expire_time']     = $expire_time;
                    $invoice_no = $dataobj['number'];
                    
                    $url                        = "api/v1/product/recharge"; 
                    $output                     = $this->PostFunction($url,$dataobj);
                    $decode                     = json_decode($output,true);
                    $data = [];
                    if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
                    {
                        $data['access_token']       = $access_token;
                        $data['user_name']          = $user_name;
                        $data['Expire_time']        = $expire_time;
                        $data['user_available']     = 0;
                        $data['Arrears_days']       = 0;
                      
                        $UpadteUser = $this->UpdateUser($data);
                      
                        $this->paymentRecordsInsertWithDate($user_name,$begin_date,$phone,$expire_time,$invoice_no,$response['transactionId']);
                          
                        $rs_mbt = $this->getpaymentrecords($user_name,$begin_date,$phone,$expire_time,$invoice_no);
                        
                        PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                        
                        return response()->json([
                            'status'=>200,
                            'message'=>'Order updated successfully.'
                        ]);
                    }else
                    {
                      return response()->json(['status'=>400,'message'=>$decode['message']]);
                    }
                }else{
                    $this->failedpaymentRecordsInsertWithDate($order->user_name,$order->begin_date,$order->phone,$order->expire_time,$number,$oreder_id,$order->amount,$order->payment_id,$order->created_at);
                    PendingPayment::where('order_no', $oreder_id)->update(['status' => '2']);
                }
            }elseif($order->payment_id == '10'){
                
                $payment = WaveCallback::where('orderId', $oreder_id)->first();
                
                if($payment->status == 'PAYMENT_CONFIRMED'){
                
                    $access_token = $this->gettoken();
                    $dataobj["access_token"]    = $access_token;
                    $dataobj["user_name"]       = $user_name;
                    $dataobj["order_no"]        = $oreder_id;
                    $dataobj['pay_type_id']     = $payment_id; 
                    $dataobj['pay_num']         = $amount;  
                    $dataobj['number']          = $number;  
                    $dataobj['Begin_date']      = $begin_date;
                    $dataobj['Expire_time']     = $expire_time;
                    $invoice_no = $dataobj['number'];
                    
                    $url                        = "api/v1/product/recharge"; 
                    $output                     = $this->PostFunction($url,$dataobj);
                    $decode                     = json_decode($output,true);
                    $data = [];
                    if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
                    {
                        $data['access_token']       = $access_token;
                        $data['user_name']          = $user_name;
                        $data['Expire_time']        = $expire_time;
                        $data['user_available']     = 0;
                        $data['Arrears_days']       = 0;
                      
                        $UpadteUser = $this->UpdateUser($data);
                      
                        $this->paymentRecordsInsertWithDate($user_name,$begin_date,$phone,$expire_time,$invoice_no,$payment->transactionId);
                          
                        $rs_mbt = $this->getpaymentrecords($user_name,$begin_date,$phone,$expire_time,$invoice_no);
                        
                        PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                        
                        return response()->json([
                            'status'=>200,
                            'message'=>'Order updated successfully.'
                        ]);
                    }else
                    {
                      return response()->json(['status'=>400,'message'=>$decode['message']]);
                    }
                }else{
                    $this->failedpaymentRecordsInsertWithDate($order->user_name,$order->begin_date,$order->phone,$order->expire_time,$number,$oreder_id,$order->amount,$order->payment_id,$order->created_at);
                    PendingPayment::where('order_no', $oreder_id)->update(['status' => '2']);
                }
            }
        }
        
        return response()->json([
            'status'=>200,
            'data'=>null,
            'message'=>'Payment Details.'
        ]);
    }
    
    public function mbtmobilesuccess(Request $request) {
      
     
         $secretKey = $this->direct_key; 
         $cipherText = $request->encdata;
        
        try{

            if (!static::isKeyLengthValid($secretKey)) {
                throw new \InvalidArgumentException("Secret key's length must be 128, 192 or 256 bits");
            }
          $encoded = base64_decode($cipherText);
          $initVector = substr($encoded, 0,0);
          $data = $encoded;
          $decoded = openssl_decrypt(
                $data,
                static::CIPHER,
                $secretKey,
                OPENSSL_RAW_DATA,
                $initVector
            );

            //manish array start
            
            $myString=$decoded;
            $trim = substr($myString, 6);
            $myArray = explode('|', $trim);
            
            foreach($myArray as $newarr)
            {
            $whatIWant[] = substr($newarr, strpos($newarr, "=") + 1);  
            }
                $fldBankRefNb= $whatIWant[0];
                $fldMerchCode= $whatIWant[1];
                $fldMerchRefNbr= $whatIWant[2];
                $fldDatTimeTxn= $whatIWant[3];
                $fldTxnScAmt= $whatIWant[4];
                $fldTxnAmt= $whatIWant[5];
                $fldSucStatFlg= $whatIWant[6];
                $fldFailStatFlg= $whatIWant[7];
                $fldClientCode= $whatIWant[8];
                $fldClientAccount= $whatIWant[9];
                $fldRef1= $whatIWant[10];
                $fldRef2= $whatIWant[11];
                $fldRef3= $whatIWant[12];
                
                 DB::table('payment_new')
                            ->where('order_id',$fldMerchRefNbr)
                            ->update(['admin_status' => 1]);
              
            
            
            //manish array end
            // DB::
                return view('front.success', compact('fldBankRefNb','fldMerchCode','fldMerchRefNbr','fldDatTimeTxn','fldTxnScAmt','fldTxnAmt'));

         
            
        } catch (\Exception $e) {
            // Operation failed
            return new static(isset($initVector), null, $e->getMessage());
        }
            
        // print_r($request->all());
        
        // $encdata = $request->encdata;
        // print_r($encdata);
        

        // return response()->json([
        //     'status'=>200,
        //     'message'=>'This is success page.'
        // ]);
        
    }
    
    
    // public function mbtmobilesuccess() {
       
        
    //     $url = "myapp://example.com/?kbzbanking=KBZDIRECT";
        
    //     return response()->json([
    //         'responseCode'=>000,
    //         'cb_url'=>$url,
    //         'responseMessage'=>'Operation Success'
    //     ]);
    // }
    
    public function mbtmobilefailure() {
        return response()->json([
            'status'=>200,
            'message'=>'This is failure page.'
        ]);
    }
    
    public function kbz_callback_url(Request $request)
    
    {
        return $request->all();
    }
    
    public function aya_access_token(Request $request)
    {
        $api_url          = $this->aya_api_tokenurl;
        $consumer_key     = $this->aya_consumer_key;
        $consumer_secret  = $this->aya_consumer_secret;
        
        $grant_type       = $this->aya_grant_type;
        $str              = $consumer_key.':'.$consumer_secret;
        $token            = base64_encode($str);
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => $api_url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => 'grant_type='.$grant_type,
          CURLOPT_HTTPHEADER => array(
            'Authorization: Basic '.$token,
            'Content-Type: application/x-www-form-urlencoded'
          ),
        ));
        
        $result = curl_exec($curl);
        $response = json_decode($result, true);
        curl_close($curl);
        
        if(!empty($response)){
            return response()->json([
                'status'=>200,
                'data'=>$response,
                'message'=>'Access Credentials.'
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'Something went wrong!'
            ]);
        }

    }
    
    public function aya_merchant_login(Request $request)
    {
        $api_url      = $this->aya_api_baseurl.'/login';
        $phone        = $this->aya_phone;
        $password     = $this->aya_password;
        $token        = $request->token;
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $api_url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => 'phone='.$phone.'&password='.$password,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            'Token: Bearer '.$token
          ),
        ));
        
        $result = curl_exec($curl);
        $response = json_decode($result, true);
        curl_close($curl);
        
        if(!empty($response)){
            return response()->json([
                'status'=>200,
                'data'=>$response,
                'message'=>'Login Successfully.'
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'Something went wrong!'
            ]);
        }

    }
    
    public function aya_request_payment(Request $request)
    {
        $api_url                 = $this->aya_api_baseurl.'/requestPushPayment';
        $token                   = $request->token;
        $access_token            = $request->access_token;
        $customerPhone           = $request->customerPhone;
        $amount                  = $request->amount;
        $currency                = $request->currency;
        $externalTransactionId   = $request->externalTransactionId;
        $externalAdditionalData  = $request->externalAdditionalData;
   
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $api_url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => 'customerPhone='.$customerPhone.'&amount='.$amount.'&currency='.$currency.'&externalTransactionId='.$externalTransactionId.'&externalAdditionalData='.$externalAdditionalData,
          CURLOPT_HTTPHEADER => array(
            'Token: Bearer '.$access_token,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept-Language: en',
            'Authorization: Bearer '.$token
          ),
        ));
        
        $result = curl_exec($curl);
        $response = json_decode($result, true);
        curl_close($curl);
        
        if(!empty($response)){
            return response()->json([
                'status'=>200,
                'data'=>$response,
                'message'=>'Payment requested Successfully.'
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'Something went wrong!'
            ]);
        }

    }
    
    // public function ayacallback(Request $request) 
    // {
    //     // $check = array([
    //     //     $request->all()
    //     // ]);
        
    //     // $check = $request->all();
        
    //     // $nirbhay=json_encode($check);
    //     // // dd($nirbhay);
        
    //     // CborderDetail::create([
    //     //     'orders'=>$nirbhay
    //     // ]);
        
        
        
    //     $cipher ="AES-256-ECB";
        
    //     $key = 'dac9aoaP2lUKwurcqO1XXoXkfwMuiVgn';
        
    //     $ciper_new = '{"paymentResult":"AONtJKpM7MJ5k1epYN12lr59YwAec\/D2PwKU6\/k7NsnLBbmu7nEuwHlQIEAe4Seepe8TgnJGHu7m+4yrRUTZVGK4eATcL485f6GmQDThFOxj3mo3YXJ2w4hXZ2CentqgfG6Ftaz8W08ItTo4p5rnXrGmiIl1dWm+4kedlWy+NmxwzYjaiSMkFG3YBpQ\/m1pVpA8mEkEFJTDm2vZ\/oXMbbtaKsy4lnQTEKWat2v+TOe2AoIpuzi0Ghvi7vCpEgxCjbQHQuVYt6xYnbZZLeX2D+OzEK465FvRt4qMgaysrbfEHwhopqFp+Xj01SFOh3FJT9joNaHljgSj7\/nt91fq13T3t4NTinEXRP4dgiomQriscGZ2et7s5dVhb37XWoB09WI3yB6B7KLPPSuU7PjiAD4fvzWfvxuD4fmnkVH8Sfk53LEmaZWMq9G54bA\/LM\/LVmlflq21E\/lcWfc704O1Q1ON0hw60B7EPKHi+ft0X+deOAkPLHkMWAuMIucZ+VdXmYt5r8Hh7OiuWzrdpFICLFvatQ4FpHOyMTPRp+hS\/C+LqeBsdT\/KJyqcSpVO+kmbDTQ6mSkcV6aVzBXwcwWdkCuDGEED29Wil8nM\/7ecuBUPsbdHrXZzqdMHicCHTzDSXOY\/QKPvea9TZLSOE8Yp0t6ZQlgbKacKDjlUuTTRKHaJ4\/2uW7pvQP1\/8vNFffwmXadbGgFQslJtXFFCWIkJ1GvOEkyz93r9h\/pH6GmUQmx2nrtktS6it6vhRw2pdkPiJ7tEP04yJkHmwHGgMqAxLh5BpbP7vyZbpdZm0eqFcKh3RAOg7Ej+4PI\/MjZGMx0AP","checksum":"37a901efde23c5987898d82ce492cbcedb08cd53f90ee336fb7e1426aa5abefe","refundResult":null}';
        
        
    //     // Encryption
    //     $chiperRaw = openssl_encrypt($ciper_new, $cipher, $key, OPENSSL_RAW_DATA);
      
    //     $ciphertext = trim(base64_encode($chiperRaw));
        
    //     $cipherHex = bin2hex($chiperRaw);
      
        
    //     $chiperRaw = base64_decode($ciphertext);
    //     //dd($chiperRaw);
        
        
        
    //     $originalData = openssl_decrypt($ciper_new, $cipher, $key, OPENSSL_RAW_DATA);
        
    //     dd($originalData);
        
       
        
    //     return $originalData;
        
    //     $decrypted_txt = encrypt_decrypt($originalData, $key);
    //     echo "Decrypt Data:".$decrypted_txt;
        
    //     return response()->json([
    //         'status'=>200,
    //         'message'=>'Decrypt Data:'.$decrypted_txt
    //     ]);
    // }
    
    public function ayacallback(Request $request) 
    {
        $result = $request->all();
        $data = $result['paymentResult'];
        $cipher ="AES-256-ECB";
        $key = $this->aya_enc_key;

        // Decryption
        $chiperRaw = base64_decode($data);
        $originalData = openssl_decrypt($chiperRaw, $cipher, $key, OPENSSL_RAW_DATA);
        // return $originalData;
        CborderDetail::create([
            'orders'=>$originalData
        ]);
        
        AyaCallback::create([
            'orders'=>$originalData
        ]);
        
        //$check = '{"name":"Subscriber Pay Online Merchant","desc":"Subscriber Pay Online Merchant","currency":"MMK","fees":{"debitFee":0,"creditFee":0},"status":"done","createdAt":"2022-02-21T10:36:39.259Z","transRefId":"62136b305f4a066d9303b4de","extMachId":null,"externalTransactionId":"62136b16e641f","referenceNumber":"62136b1a1833dfa56ec08153","totalAmount":24900,"amount":24900,"externalAdditionalData":"Merchant New Payment","customer":{"id":"61ea7786a622678121330ac8","name":"MBT TESTING TWO","phone":"09787994306"},"merchant":{"id":"61dbc86c290dc734a48c400d","name":"MYANMAR BROADBAND TELECOM","phone":"09963612888"}}';
        $new_data = json_decode($originalData);
        //dd($new_data->externalTransactionId);
        
        $check_trans = PaymentQuery::where('order_id', $new_data->externalTransactionId)->first();
        
        if($new_data->externalTransactionId == $check_trans->order_id){
            $rs = PaymentNew::where('order_id', $new_data->externalTransactionId)->update(['admin_status'=>'1']);
            $rs1 = PaymentQuery::where('order_id', $new_data->externalTransactionId)->update(['admin_status'=>'1']);
        }
    }
    
    
    public function kbz_mobile_payment(Request $request)
    {
        date_default_timezone_set('Asia/Kolkata');
        
        $api_url          = 'https://mirror.kbzbank.com/B001/directpay';
        
        $fldMerchCode     = 'EPI0008';
        $fldTxnCurr       = 'MMK';
        $fldTxnAmt        = $request->amount;
        $fldTxnScAmt      = '0';
        $fldSucStatFlg    = 'N';
        $fldFailStatFlg   = 'N';
        $fldClientCode    = '101';
        $fldMerchRefNbr   = $this->generateRandomString();
        $fldDatTimeTxn    = date('d/m/Y h:i:s', time());
        
        // $string = 'fldClientCode='.$fldClientCode.'|fldMerchCode='.$fldMerchCode.'|fldTxnCurr='.$fldTxnCurr.'|fldTxnAmt='.$fldTxnAmt.'|fldTxnScAmt='.$fldTxnScAmt.'|fldMerchRefNbr='.$fldMerchRefNbr.'|fldSucStatFlg='.$fldSucStatFlg.'|fldFailStatFlg='.$fldFailStatFlg.'|fldDatTimeTxn='.$fldDatTimeTxn;
        // $string = 'fldClientCode=101|fldMerchCode=EPI0008|fldTxnCurr=MMK|fldTxnAmt=1000|fldTxnScAmt=0|fldMerchRefNbr=ABC00123|fldSucStatFlg=N|fldFailStatFlg=N|fldDatTimeTxn=20/05/2022 11:45:32';
        // $md5_enc = md5($string);
        // dd($md5_enc);
        $new_string = 'fldClientCode=101|fldMerchCode=EPI0008|fldTxnCurr=MMK|fldTxnAmt=1000|fldTxnScAmt=0|fldMerchRefNbr=ABC00123|fldSucStatFlg=N|fldFailStatFlg=N|fldDatTimeTxn=20/05/2022 11:45:32|checkSum=75e79474e518836668e5b217c0ce10af';
        
        $key = 'S%Y#N@';
        
        $iv  = base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-cbc')));
        
        $encodedEncryptedData = base64_encode(openssl_encrypt($new_string, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, base64_decode($iv)));
        
        dd($encodedEncryptedData);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $api_url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => 'customerPhone='.$customerPhone.'&amount='.$amount.'&currency='.$currency.'&externalTransactionId='.$externalTransactionId.'&externalAdditionalData='.$externalAdditionalData,
          CURLOPT_HTTPHEADER => array(
            'Token: Bearer '.$access_token,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept-Language: en',
            'Authorization: Bearer '.$token
          ),
        ));
        
        $result = curl_exec($curl);
        $response = json_decode($result, true);
        curl_close($curl);
        
        if(!empty($response)){
            return response()->json([
                'status'=>200,
                'data'=>$response,
                'message'=>'Payment requested Successfully.'
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'Something went wrong!'
            ]);
        }

    }
    
    public function aya_payment_status(Request $request) 
    {
        $order_id = $request->order_id;
        
        $response = AyaCallback::get();
        
        foreach($response as $resp){
            $response_new = json_decode($resp['orders'], true);
            
            if($order_id == $response_new['externalTransactionId']){
                $details = $response_new;
            }
        }
        
        if(!empty($details)){
            
            // $rs = PaymentNew::where('order_id', $order_id)->update(['admin_status'=>'1']);
            // $rs1 = PaymentQuery::where('order_id', $order_id)->update(['admin_status'=>'1']);
            
            return response()->json([
                'status'=>200,
                'data'=>$details,
                'message'=>'Payment order status.'
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'Details not found!'
            ]);
        }
    }
    
    
    
     public function kbz_payment_status(Request $request) 
    {
        $order_id = $request->order_id;
        
        $response = PaymentQuery::where('order_id', $order_id)->first();
        
        if(!empty($response)){
            return response()->json([
                'status'=>200,
                'data'=>$response,
                'message'=>'KBZ Payment order status.'
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'Something went wrong!'
            ]);
        }
    }
    
    
    
    
    public static function isKeyLengthValid($secretKey)
    {
        $length = strlen($secretKey);

        return $length == 6 || $length == 16 || $length == 24 || $length == 32;
    }
    
    public function decrypt(){
        
        $secretKey = 'K%B#Z@';
        $cipherText = 'sdaDzJZANsZWIHPDSX6%2BKOft%2BjSFvLgAUY6mzMiaadIIS6xCImlm0kSWHBsXNX0v2UOhN5GAxbpwE2k%2FGX3PBwEPhVOINg45o591%2BOs8pTAiB4uKVk7HDM6NpzW7%2FbWK9z9RUSchchhOww%2FiKKUFIJZjgPQ20aaT03kFOPTJn3A00QB5l6jBvS2OO%2BYzjmZbMVuH2y8GSKnBVVjrvnAkfg%3D';
        
        try{

            if (!static::isKeyLengthValid($secretKey)) {
                throw new \InvalidArgumentException("Secret key's length must be 128, 192 or 256 bits");
            }
                
            $encoded = base64_decode($cipherText);
            //dd($encoded);
            
            // Slice initialization vector
            $initVector = substr($encoded, 0, openssl_cipher_iv_length('aes-128-cbc'));
            //dd($initVector);
            
            // Slice encoded data
            $data = substr($encoded, static::INIT_VECTOR_LENGTH);
            //dd($data);
    
            // Trying to get decrypted text
            $decoded = openssl_decrypt(
                $data,
                static::CIPHER,
                $secretKey,
                OPENSSL_RAW_DATA,
                $initVector
            );
            
            if ($decoded === false) {
                // Operation failed
                return new static(isset($initVector), null, openssl_error_string());
            }

            // Return successful decoded object
            return new static($initVector, $decoded);
            
        } catch (\Exception $e) {
            // Operation failed
            return new static(isset($initVector), null, $e->getMessage());
        }
            
    }
    
    public function checkAppUpdate(Request $request) {
        
    
            $validator = Validator::make($request->all(), [
                'platform'      => 'max:32',
                'app_ver'       => 'max:32',
                'app_build_ver' => 'max:32',
                'api_ver'       => 'max:32',
                'device'        => '',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => $this->unauthorisedStatus,'message'=>$validator->errors()], $this->unauthorisedStatus);
            } else {
                $GuzzleClient     = new \GuzzleHttp\Client();//GuzzleHttp\Client $request->reference_number
                $GuzzleHttp       = $GuzzleClient->request('GET', 'https://play.google.com/store/apps/details?id=com.company.myanmarbroadbandtelecom&hl=en', [
                    'headers' => [
//                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'verify'  => FALSE
                ]);
                
                $response['code'] = $GuzzleHttp->getStatusCode();
                $response['res']  = $GuzzleHttp->getReasonPhrase();
                if ($response['code'] == 200) {
                    ob_start();
                    echo $GuzzleHttp->getBody();
                    $content        = ob_get_clean();
                    $CurrentVersion = $this->get_string_between($content, '<div class="BgcNfc">Current Version</div><span class="htlgb"><div class="IQ1z0d"><span class="htlgb">', '</span>');

                    // if (isset($CurrentVersion) && $CurrentVersion) {
                    //     $data['api_ver_playstore'] = $CurrentVersion;
                    //     $data['update_available']  = $CurrentVersion !== $request->app_ver ? true : false;
                    //     return response()->json(['status' => $this->successStatus,'message'=>"Data Fetched Successfully.",'data'=>$data, 'forceUpdate' => true,'url'=>'https://play.google.com/store/apps/details?id=com.company.myanmarbroadbandtelecom'], $this->successStatus);
                    // } else {
                    //     return response()->json(['status' => 404,'message'=>"Internal Server Error', 'Error', 'Data Load Failed, Please try again.",'data'=>array()], $this->unauthorisedStatus);
                    // }
                    
                    if($request->platform == 'Android'){
                        if ($request->app_ver) {
                            $data['api_ver_playstore'] = 3.2;
                            if($data['api_ver_playstore'] > $request->app_ver){
                                $data['update_available']  =  true;
                            }else{
                                $data['update_available']  =  false;
                            }
                            return response()->json(['status' => $this->successStatus,'message'=>"Data Fetched Successfully.",'data'=>$data, 'forceUpdate' => true,'url'=>'https://play.google.com/store/apps/details?id=com.company.myanmarbroadbandtelecom'], $this->successStatus);
                        } else {
                            return response()->json(['status' => 404,'message'=>"Internal Server Error', 'Error', 'Data Load Failed, Please try again.",'data'=>array()], $this->unauthorisedStatus);
                        }
                    }elseif($request->platform == 'iOS'){
                        if ($request->app_ver) {
                            $data['api_ver_playstore'] = 3.2;
                            if($data['api_ver_playstore'] > $request->app_ver){
                                $data['update_available']  =  true;
                            }else{
                                $data['update_available']  =  false;
                            }
                            
                            return response()->json(['status' => $this->successStatus,'message'=>"Data Fetched Successfully.",'data'=>$data, 'forceUpdate' => true,'url'=>'https://apps.apple.com/my/app/id1617950941'], $this->successStatus);
                        } else {
                            return response()->json(['status' => 404,'message'=>"Internal Server Error', 'Error', 'Data Load Failed, Please try again.",'data'=>array()], $this->unauthorisedStatus);
                        }
                    }
                    
                } else {
                   return response()->json(['status' => 404,'message'=>"Internal Server Error', 'Error', 'Data Load Failed, Please try again.",'data'=>array()], $this->unauthorisedStatus);
                }
            }
    }
    
    public function get_string_between($string, $start, $end) {
        $string = ' '.$string;
        $ini    = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;

        return substr($string, $ini, $len);
    }
    
    public function removeUser(Request $request){
        $validator = \Validator::make($request->all(),
        [
           'user_id'   => 'required'
        ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
        $user_id = $request->user_id;
        
        $remove_user = User::where('id', $user_id)->delete();
        
        if($remove_user){
            return response()->json([
                'status'=>200,
                'message'=>'User removed successfully.'
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'Something went wrong!'
            ]);
        }
    }
    
    public function mbtkbzpaycheck(Request $request)
    {  
        $validator = \Validator::make($request->all(),
        [
           'order_id'   => 'required'
        ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
        $order_id = $request->order_id;
        
        $check_order = CborderDetail::where('orders', $order_id)->first();
        
        if(!empty($check_order)){
            
            // $pay_details = PendingPayment::where('order_no', $order_id)->first();
        
            // $user_name   = $pay_details->user_name;
            // $order_no    = $pay_details->order_no;
            // $payment_id  = $pay_details->payment_id;
            // $amount      = $pay_details->amount;
            // $number      = $pay_details->number;
            // $begin_date  = $pay_details->begin_date;
            // $expire_time = $pay_details->expire_time;
            
            // $access_token = $this->gettoken();
            // $dataobj["access_token"]    = $access_token;
            // $dataobj["user_name"]       = $user_name;
            // $dataobj["order_no"]        = $order_no;
            // $dataobj['pay_type_id']     = $payment_id; 
            // $dataobj['pay_num']         = $amount;  
            // $dataobj['number']          = $number;  
            // $dataobj['Begin_date']      = $begin_date;
            // $dataobj['Expire_time']     = $expire_time;
            // $invoice_no = $dataobj['number'];
            
            // $url                        = "api/v1/product/recharge"; 
            // $output                     = $this->PostFunction($url,$dataobj);
            // $decode                     = json_decode($output,true);
            // $data = [];
            // if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
            // {
            //     $data['access_token']       = $access_token;
            //     $data['user_name']          = $pay_details->user_name;
            //     $data['Expire_time']        = $pay_details->expire_time;
            //     $data['user_available']     = 0;
            //     $data['Arrears_days']       = 0;
              
            //     $UpadteUser = $this->UpdateUser($data);
              
            //     $this->paymentRecordsInsertWithDate($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                  
            //     $rs_mbt = $this->getpaymentrecords($pay_details->user_name,$pay_details->begin_date,$pay_details->phone,$pay_details->expire_time,$invoice_no);
                
            //     $rs = PaymentNew::where('order_id', $request->order_id)->update(['admin_status'=>'1']);
            //     $rs1 = PaymentQuery::where('order_id', $request->order_id)->update(['admin_status'=>'1']);
                
            //     return response()->json([
            //         'status'=>200,
            //         'message'=>'Payment done successfully.'
            //     ]);
            // }else
            // {
            //   return response()->json(['status'=>400,'message'=>$decode['message']]);
            // }
            
            return response()->json([
                'status'=>200,
                'message'=>'Payment done successfully.'
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'Payment not done.'
            ]);
        }
    }
    
    // Wavepay
    
    public function wave_request_payment(Request $request)
    {
        $time_to_live_in_seconds = $this->wave_live_seconds;
        $merchant_id = $this->wave_merchnt_id;
        $order_id = $request->order_id;
        $product = $request->product;
        $amount = $request->amount;
        $backend_result_url = $this->wave_callback_url;
        $merchant_reference_id = $request->mbt_id.'-'.$order_id;
        // $merchant_reference_id = $this->generateRandomString();
        
        $secret_key = $this->wave_secret_key;
        
        $string = implode("", [
         $time_to_live_in_seconds,
         $merchant_id,
         $order_id,
         $amount,
         $backend_result_url,
         $merchant_reference_id]);
         
        $hash = hash_hmac('sha256', $string, $secret_key);
        
        $wave_params = [
            "merchant_id" => $merchant_id,
            "order_id" => $order_id,
            "merchant_reference_id" => $merchant_reference_id,
            "frontend_result_url" => url('api/wave-mbt-return'),
            "backend_result_url" => $backend_result_url,
            "amount" => $amount,
            "time_to_live_in_seconds" => $time_to_live_in_seconds,
            "payment_description" => $request->mbt_id,
            "currency" => "MMK",
            "hash" => $hash,
            "merchant_name" => "MBT",
            "items" => json_encode([
             ['name' => $product, 'amount' => $amount, 'mbt_id' => $request->mbt_id]
            ])
        ];
         
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->wave_base_url.'payment',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => json_encode($wave_params),
          CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json',
          ),
        ));
        
        $result = curl_exec($curl);
        $response = json_decode($result, true);
        
        if(!empty($response['transaction_id'])){
            return response()->json([
                'status'=>200,
                'message'=>'Payment initiated successfully.',
                'url'=>$this->wave_base_url.'authenticate?transaction_id='.$response['transaction_id']
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>$response['message']
            ]);
        }
    }
    
    public function wave_callback_payment(Request $request)
    {
        $rs = WaveCallback::create([
            'additionalField1' => $request->additionalField1, 
            'additionalField2' => $request->additionalField2, 
            'additionalField3' => $request->additionalField3, 
            'additionalField4' => $request->additionalField4, 
            'additionalField5' => $request->additionalField5, 
            'amount' => $request->amount, 
            'backendResultUrl' => $request->backendResultUrl, 
            'currency' => $request->currency, 
            'frontendResultUrl' => $request->frontendResultUrl, 
            'hashValue' => $request->hashValue, 
            'initiatorMsisdn' => $request->initiatorMsisdn, 
            'merchantId' => $request->merchantId, 
            'merchantReferenceId' => $request->merchantReferenceId, 
            'orderId' => $request->orderId, 
            'paymentDescription' => $request->paymentDescription, 
            'paymentRequestId' => $request->paymentRequestId, 
            'requestTime' => $request->requestTime, 
            'status' => $request->status, 
            'timeToLiveSeconds' => $request->timeToLiveSeconds, 
            'transactionId' => $request->transactionId,
        ]);
    }
    
    public function check_user(Request $request) 
    {
        $access_token    = $this->gettoken(); //get_token
        
        $user_name       = $request->user_name;
        $data            = "access_token={$access_token}&user_name={$user_name}";
        $url             = "api/v1/user/view?".$data; 
        $output          = $this->GetFunction($url);
        $response        = json_decode($output, true);
        
        $url1          = "api/v1/package/users-packages?".$data; 
        $output1       = $this->GetFunction($url1);
        $response1     = json_decode($output1,true);
        
        $date = $response['data']['user_expire_time'];  //2023-04-25 23:00
        $createDate = new DateTime($date);
        $expire_date = $createDate->format('Y-m-d'); //2023-04-25
        $today_date = date('Y-m-d'); //2023-03-13
        
        $earlier = new DateTime($expire_date);
        $later = new DateTime($today_date);
        $diff = $later->diff($earlier)->format("%r%a"); //-3
        $user_available = $response['data']['user_available'];
        $days = $this->number_days;
        $billing_name = $response1['data'][0]['checkout_amount'];
        
        if($days < $diff){
            return response()->json([
                'status'=>200,
                'message'=>'More than '.$days.' days left.',
                'user_available'=>$user_available,
                'expiry_date'=>$date,
                'billing_name'=>$billing_name
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'Less than '.$days.' days left.',
                'user_available'=>$user_available,
                'expiry_date'=>$date,
                'billing_name'=>$billing_name
            ]);
        }
    }
    
    // public function checknewuser(Request $request) 
    // {
    //     $access_token    = $this->gettoken(); //get_token
        
    //     $user_name       = $request->user_name;
    //     $data            = "access_token={$access_token}&user_name={$user_name}";
    //     $url             = "api/v1/user/view?".$data; 
    //     $output          = $this->GetFunction($url);
    //     $response        = json_decode($output, true);
        
    //     $installation_date = $response['data']['Installation_date']; //2023-5-1
    //     if($installation_date != '0'){ //NDGQ2205033
    //         $today_date = date('Y-m-d'); //2023-05-04
            
    //         $earlier = new DateTime($installation_date);
    //         $later = new DateTime($today_date);
    //         $diff = $earlier->diff($later)->format("%r%a"); //3
    //         $days = $this->new_user_days; // 7
            
    //         if($diff > $days){
    //             return response()->json([
    //                 'status'=>400,
    //                 'message'=>'Old user.',
    //                 'installation_date'=>$installation_date
    //             ]);
    //         }else{
    //             return response()->json([
    //                 'status'=>200,
    //                 'message'=>'New user.',
    //                 'installation_date'=>$installation_date
    //             ]);
    //         }
    //     }else{ // Apptest03
    //         return response()->json([
    //             'status'=>400,
    //             'message'=>'Old user.',
    //             'installation_date'=>$installation_date
    //         ]);
    //     }
    // }
    
    public function checknewuser(Request $request) 
    {
        $access_token    = $this->gettoken(); //get_token
        
        $user_name       = $request->user_name;
        $data            = "access_token={$access_token}&user_name={$user_name}";
        $url             = "api/v1/user/view?".$data; 
        $output          = $this->GetFunction($url);
        $response        = json_decode($output, true);
        
        if(empty($response['data'])){
            return response()->json([
                'status'=>400,
                'message'=>'User not exists.',
            ]);
        }
        
        $payments = PaymentProcess::where('status', '1')->orderBy('id', 'asc')->get();
        $process = 0;
        foreach($payments as $payment){
            $count = strlen($payment->bind_id);
            $check = substr($user_name, 0, $count);
            
            if(strtoupper($payment->bind_id) == strtoupper($check)){
                $process = 1;
            }
        }
        
        if($process == '1'){
            return response()->json([
                'status'=>202,
                'message'=>'Online payment is currently not supported, please contact the bill collector. Thank you',
            ]);
        }
        
        $installation_date = $response['data']['Installation_date']; //2023-5-1
        $expireation_date = $response['data']['user_expire_time']; //2025-07-31 23:00

        if($installation_date != '0'){ //NDGQ2205033
            $today_date = date('Y-m-d'); //2023-05-04
            
            $todaydate = new DateTime($today_date);  // 2024-02-16 00:00:00.0 Asia/Yangon (+06:30)
            $install_date = new DateTime($installation_date);  // 2023-09-20 00:00:00.0 Asia/Yangon (+06:30)
            $expire_date = new DateTime($expireation_date);  // 2024-02-05 23:00:00.0 Asia/Yangon (+06:30)
            
            $diff = $install_date->diff($todaydate)->format("%r%a"); //149
            $diff1 = $todaydate->diff($expire_date)->format("%r%a"); //-10
            
            $days = $this->new_user_days; //20
            $days1 = $this->new_user_days1; //20
            
            if($diff < $days && $diff1 < $days1){
                return response()->json([
                    'status'=>200,
                    'message'=>'New user.',
                    'installation_date'=>$installation_date
                ]);
            }else{
                return response()->json([
                    'status'=>400,
                    'message'=>'Old user.',
                    'installation_date'=>$installation_date
                ]);
            }
        }else{ // Apptest03
            return response()->json([
                'status'=>400,
                'message'=>'Old user.',
                'installation_date'=>$installation_date
            ]);
        }
    }
    
    public function checkexpiretime(Request $request) 
    {
        $access_token    = $this->gettoken(); //get_token
        
        $package_id      = $request->package_id;
        $user_name       = $request->user_name;
        
     
        $data         = "access_token={$access_token}&user_name={$user_name}";
        $url          = "api/v1/package/users-packages?".$data; 
        $output       = $this->GetFunction($url);
        $decode       = json_decode($output,true);
        if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
        {
            $discount = $this->discount;
            $commercial_tax = $this->commercial_tax;
            
            $GetPackageDescription = Package::where('status',1)->where('id',$package_id)->first();
            if($GetPackageDescription->plan_type == 'Monthly'){
                $extra_month = ExtraMonth::where('duration', $GetPackageDescription->time)->first();
            }else{
                $extra_month = ExtraMonth::where('duration', '12')->first();
            }
            
            $current_date = date('Y-m-d H:i:s'); 
            
            dd($current_date);
            
            $amount = $GetPackageDescription->price;
            $new_amount = ($amount/(1 + $discount/100))/(1 + $commercial_tax/100);
            $total_amount = number_format($new_amount, 0, '.', '');
            $GetPackageDescription['discounted_price'] = $total_amount;
            $GetPackageDescription['extra_month'] = $extra_month->extra_month;
            dd($GetPackageDescription);
            
            return response()->json(['status'=>200,'message'=>'Get Package successfully!','package'=>$GetPackageDescription]);
        }
         
    }
    
    public function GetNewPackage()
    {
        $access_token = $this->gettoken(); //get_token
        if ($access_token === null) {
            return response()->json(['status' => 503, 'message' => 'Token service unavailable', 'data' => []]);
        }
        $user_name    = request('user_name');
        $data         = "access_token={$access_token}&user_name={$user_name}";
        $url          = "api/v1/package/users-packages?".$data; 
        $output       = $this->GetFunction($url);
        $decode       = json_decode($output,true);
        $MbtUserObj   = MbtBindUser::where('user_name',$user_name)->first();
      $MbtUser      = $MbtUserObj ? $MbtUserObj->user_expire_time : '';
        
        $Currurl             = "api/v1/user/view?".$data; 
        $Curroutput          = $this->GetFunction($Currurl);
        $CurrDecode          = json_decode($Curroutput,true);
        
        if(!empty($CurrDecode['data']['Installation_cost'])){
            if($CurrDecode['data']['Installation_cost'] == 'FREE'){
                $installation_cost = 0;
            }elseif($CurrDecode['data']['Installation_cost'] == 'Free'){
                $installation_cost = 0;
            }else{
                $installation_cost = $CurrDecode['data']['Installation_cost'];
            }
        }else{
            if($CurrDecode['data']['Installation_cost'] == '0'){
                $installation_cost = $CurrDecode['data']['Installation_cost'];
            }else{
                $installation_cost = 0;
            }
        }
        
        if(isset($decode['code']) && $decode['code'] == 0 && isset($decode['data'][0]['billing_name']))
        {
            $PackageName = $decode['data'][0]['billing_name'];
            $explode     = explode(" ",$PackageName); //Explode package name for get price
            $takeprice   = $explode[0];  //get price from 0 index and made package for defferent time
            $data = [];
            $data['0']    = $takeprice*1; // One Month
            $data['1']    = $takeprice*3; // Three Month
            $data['2']    = $takeprice*6; // Six Month
            $data['3']    = $takeprice*12; // twelve(1 year) Month
           
            $discount = $this->discount;
            $commercial_tax = $this->commercial_tax;
            
            if($CurrDecode['data']['group_id'] == '17'){
                $GetPackageDescription = Package::where('status',1)->orderBy('discount_price','asc')->get();
                $all_promotions = Promotion::where('status', '1')->get();
            
                foreach($GetPackageDescription as $key=>$value){
                    foreach($all_promotions as $all_promotion){
                        if($CurrDecode['data']['group_id'] == '17' && $all_promotion->promotion_type == '1'){
                            if($value->plan_type == 'Monthly'){
                                if($all_promotion->duration == $value->time){
                                    $new_packages[] = Package::where('status',1)->where('time', $all_promotion->duration)->where('plan_type', 'Monthly')->orderBy('discount_price','asc')->first();
                                }
                            }elseif($value->plan_type == 'Yearly'){
                                if($all_promotion->duration == '12'){
                                    $new_packages[] = Package::where('status',1)->where('time', $all_promotion->duration/12)->where('plan_type', 'Yearly')->orderBy('discount_price','asc')->first();
                                }
                            }
                        }
                    }
                }
                
                foreach($new_packages as $k=>$new_package){
                    if($new_package->plan_type == 'Monthly'){
                        $extra_month = Promotion::where('duration', $new_package->time)->where('promotion_type', '1')->first();
                        
                        if($new_package->time == '1'){
                            $amount = $data[0];
                        }elseif($new_package->time == '3'){
                            $amount = $data[1];
                        }elseif($new_package->time == '6'){
                            $amount = $data[2];
                        }
                    }else{
                        $extra_month = Promotion::where('duration', '12')->where('promotion_type', '1')->first();
                        
                        if($new_package->time == '1'){
                            $amount = $data[3];
                        }
                    }
                    
                    if(!empty($extra_month)){
                        $new_extra_month = $extra_month->extra_month;
                        $new_extra_days = $extra_month->extra_days;
                    }else{
                        $new_extra_month = 0;
                        $new_extra_days = 0;
                    }
                    
                    $new_amount = ($amount/(1 + $discount/100))/(1 + $commercial_tax/100)+$installation_cost;
                    $total_amount = number_format($new_amount, 0, '.', '');
                    
                    $new_package->price = $total_amount;
                    $new_package->discounted_price = $total_amount;
                    $new_package->extra_month = $new_extra_month;
                    $new_package->extra_day = $new_extra_days;
                }
            }else{
                $GetPackageDescription = Package::where('status',1)->orderBy('discount_price','asc')->get();
                $all_promotions = Promotion::where('status', '1')->get();
            
                foreach($GetPackageDescription as $key=>$value){
                    foreach($all_promotions as $all_promotion){
                        if($CurrDecode['data']['group_id'] != '17' && $all_promotion->promotion_type != '1'){
                            if($value->plan_type == 'Monthly'){
                                if($all_promotion->duration == $value->time){
                                    $new_packages[] = Package::where('status',1)->where('time', $all_promotion->duration)->where('plan_type', 'Monthly')->orderBy('discount_price','asc')->first();
                                }
                            }elseif($value->plan_type == 'Yearly'){
                                if($all_promotion->duration == '12'){
                                    $new_packages[] = Package::where('status',1)->where('time', $all_promotion->duration/12)->where('plan_type', 'Yearly')->orderBy('discount_price','asc')->first();
                                }
                            }
                        }
                    }
                }
                
                foreach($new_packages as $k=>$new_package){
                    if($new_package->plan_type == 'Monthly'){
                        $extra_month = Promotion::where('duration', $new_package->time)->where('promotion_type', '1')->first();
                        
                        if($new_package->time == '1'){
                            $amount = $data[0];
                        }elseif($new_package->time == '3'){
                            $amount = $data[1];
                        }elseif($new_package->time == '6'){
                            $amount = $data[2];
                        }
                    }else{
                        $extra_month = Promotion::where('duration', '12')->where('promotion_type', '1')->first();
                        
                        if($new_package->time == '1'){
                            $amount = $data[3];
                        }
                    }
                    
                    if(!empty($extra_month)){
                        $new_extra_month = $extra_month->extra_month;
                        $new_extra_days = $extra_month->extra_days;
                    }else{
                        $new_extra_month = 0;
                        $new_extra_days = 0;
                    }
                    
                    $new_amount = ($amount/(1 + $discount/100))/(1 + $commercial_tax/100)+$installation_cost;
                    $total_amount = number_format($new_amount, 0, '.', '');
                    
                    $new_package->price = $total_amount;
                    $new_package->discounted_price = $total_amount;
                    $new_package->extra_month = $new_extra_month;
                    $new_package->extra_day = $new_extra_days;
                }
            }
       
            return response()->json(['status'=>200,'message'=>'Get Package successfully!','package'=>$new_packages]);
           
        }else
        {
            return response()->json(['status'=>400,'message'=>$decode['message'],'version'=>$decode['version']]);
        }
    }
  
    public function getnewpackage_language(Request $request)
    {
        $access_token    = $this->gettoken(); //get_token
        
        $user_name       = $request->user_name;
        $language_id     = $request->language_id;
        
        $validator = \Validator::make($request->all(),
        [
           'user_name'    => 'required',
           'language_id'    => 'required'
        ]);
       
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
        $data            = "access_token={$access_token}&user_name={$user_name}";
        $url             = "api/v1/user/view?".$data; 
        $output          = $this->GetFunction($url);
        $response        = json_decode($output, true);
        
        $group = $response['data']['group_id'];
        if(!empty($group)){
            if($language_id== 0){
                $language = 'description';
                $message = 'English Language!';
            }elseif($language_id==1){
                $language = 'chinese AS description';
                $message = 'Chinese Language!';
            }elseif($language_id==2){
                $language = 'myanmar AS description';
                $message = 'Maynmar Language!';
            }
            
            if($group == '17'){
                $englanguage= Promotion::select($language)->where('status', '1')->where('promotion_type', '1')->orderBy('sort_by', 'asc')->get();
            }else{
                $englanguage= Promotion::select($language)->where('status', '1')->where('promotion_type', '0')->orderBy('sort_by', 'asc')->get();
            }
         
            return response()->json([
                'status'=>200,
                'message'=>$message,
                'user_data'=>$englanguage
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'No Group Found.',
                'user_data'=>null
            ]);
        }
    }
    
    // public function GetNewPackageInformation()
    // {
    //     $access_token = $this->gettoken(); //get_token  
    //     $user_name    = request('user_name');
        
    //     $data         = "access_token={$access_token}&user_name={$user_name}";
    //     $url          = "api/v1/package/users-packages?".$data; 
    //     $output       = $this->GetFunction($url);
    //     $decode       = json_decode($output,true);

    //     $MbtUser      = MbtBindUser::where('user_name',$user_name)->first();
    //     $UserAvailable = array('Normal','Disabled','Stop Product','Paused','Non Activated');
     
    //     $Currurl             = "api/v1/user/view?".$data; 
        
    //     $Curroutput          = $this->GetFunction($Currurl);
    //     $CurrDecode          = json_decode($Curroutput,true);
     
    //     if(empty($CurrDecode['data']['Arrears_days'])){
    //         $arrears_days = 0;
    //     }else{
    //         $arrears_days = $CurrDecode['data']['Arrears_days'];
    //     }
      
    //     if($MbtUser)
    //     {
    //         $MbtBindUser = MbtBindUser::where('user_name',$user_name)->update([
    //          'er_id'                => $CurrDecode['data']['user_id'],
    //          'user_name'            => $CurrDecode['data']['user_name'],
    //          'user_real_name'       => $CurrDecode['data']['user_real_name'],
    //          'Monthly_Cost'         => $decode['data'][0]['billing_name'],
    //          'arrears_days'         => $arrears_days,
    //          'group_id'             => $CurrDecode['data']['group_id'],
    //          'region_id'            => $CurrDecode['data']['region_id'],
    //          'user_create_time'     => $CurrDecode['data']['user_create_time'],
    //          'user_update_time'     => $CurrDecode['data']['user_update_time'],
    //          'user_expire_time'     => $CurrDecode['data']['user_expire_time'],
    //          'user_status'          => $CurrDecode['data']['user_status'],
    //          'balance'              => $CurrDecode['data']['balance'],
    //          'mgr_name_create'      => $CurrDecode['data']['mgr_name_create'],
    //          'mgr_name_update'      => $CurrDecode['data']['mgr_name_update'],
    //          'user_start_time'      => $CurrDecode['data']['user_start_time'],
    //          'user_stop_time'       => $CurrDecode['data']['user_stop_time'],
    //          'phone'                => $CurrDecode['data']['phone'],
    //          'email'                => $CurrDecode['data']['email'],
    //          'create_visitor_num'   => $CurrDecode['data']['create_visitor_num'],
    //          'user_available'       => $CurrDecode['data']['user_available'],
    //          'question1'            => $CurrDecode['data']['question1'],
    //          'answer1'              => $CurrDecode['data']['answer1'],
    //          'question2'            => $CurrDecode['data']['question2'],
    //          'answer2'              => $CurrDecode['data']['answer2'],
    //          'question3'            => $CurrDecode['data']['question3'],
    //          'answer3'              => $CurrDecode['data']['answer3'],
    //          'school_type'          => $CurrDecode['data']['school_type'],
    //          'last_online'          => $CurrDecode['data']['last_online'],
    //          'last_offline'         => $CurrDecode['data']['last_offline'],
    //          'user_address'         => $CurrDecode['data']['user_address'],
    //          'salesman'             => $CurrDecode['data']['salesman'],
    //          'Area'                 => $CurrDecode['data']['Area'],
    //          'Router_type'          => $CurrDecode['data']['Router_type'],
    //          'other'                => $CurrDecode['data']['other'],
    //          'IPTV'                 => $CurrDecode['data']['IPTV'],
    //          'Installation_cost'    => $CurrDecode['data']['Installation_cost'],
    //          'Phone_number'         => $CurrDecode['data']['Phone_number'],
    //          //'city'                 => $CurrDecode['data']['city'],
    //          'GPS'                  => $CurrDecode['data']['GPS'],
    //          'Service_type'         => $CurrDecode['data']['Service_type'],
    //          'Bandwidth'            => $CurrDecode['data']['Bandwidth'],
    //          'Order_Received_Date'  => $CurrDecode['data']['Order_Received_Date'],
    //          'Promotion_type'       => $CurrDecode['data']['Promotion_type'],
    //          'Remark_Marketing'     => $CurrDecode['data']['Remark_Marketing'],
    //         //  'Monthly_Cost'         => $CurrDecode['data']['Monthly_Cost'],
    //         //  'Installation_person'  => $CurrDecode['data']['Installation_person'],
    //          'ODB_Box'              => $CurrDecode['data']['ODB_Box'],
    //          'Pon'                  => $CurrDecode['data']['Pon'],
    //          'LOID'                 => $CurrDecode['data']['LOID'],
    //          'Fiber_length'         => $CurrDecode['data']['Fiber_length'],
    //          'Initial_Contract_Validity'        => $CurrDecode['data']['Initial_Contract_Validity'],
    //          'Expected_installation_date'       => $CurrDecode['data']['Expected_installation_date'],
    //          'Installation_date'                => $CurrDecode['data']['Installation_date'],
    //          'Optical_power'            => $CurrDecode['data']['Optical_power'],
    //          'Remark'                   => $CurrDecode['data']['Remark'],
    //          'password'                 => $CurrDecode['data']['password'],
    //          'Abnormal_change'          => $CurrDecode['data']['Abnormal_change'],
    //          'User_maintenance_status'  => $CurrDecode['data']['User_maintenance_status'],
    //          'Fault_details'            => $CurrDecode['data']['Fault_details'],
    //          'Reporting_time'           => $CurrDecode['data']['Reporting_time'],
    //          'Supplementary_explanation' => $CurrDecode['data']['Supplementary_explanation'],
    //          'Marketing_maintenance'     => $CurrDecode['data']['Marketing_maintenance'],
    //          'Add_device_type'           => $CurrDecode['data']['Add_device_type'],
    //          'explanation'               => $CurrDecode['data']['explanation'],
    //          'DC_OCC'                    => $CurrDecode['data']['DC_OCC'],
    //          'LAN'                       => $CurrDecode['data']['LAN'],
    //          'delay_finance'             => $CurrDecode['data']['delay_finance'],
    //          'ODB_RX_Power'              => $CurrDecode['data']['ODB_RX_Power'],
    //          'Speed_Test'                => $CurrDecode['data']['Speed_Test'],
    //          'ODB_GPS'                   => $CurrDecode['data']['ODB_GPS'],
    //         //  'Reason_for_temporarily_unable_to_maintain' => $CurrDecode['data']['Reason_for_temporarily_unable_to_maintain'],
    //          'ALTERNATION_RECEIVE_DATE'                  => $CurrDecode['data']['ALTERNATION_RECEIVE_DATE'],
    //          'Replacement_days'                          => $CurrDecode['data']['Replacement_days'],
    //          'Nationality'                               => $CurrDecode['data']['Nationality'],
    //          'Sub_company'                               => $CurrDecode['data']['Sub_company'],
    //          'Now_package'                               => $decode['data'][0]['products_name'],
    //          'arrears_days'                              => $CurrDecode['data']['Arrears_days']
    //         ]);
    //     }
      
    //     if (array_key_exists($CurrDecode['data']['user_available'], $UserAvailable)) {
    //         $SelfProfile['user_available']      = $UserAvailable[$CurrDecode['data']['user_available']];
    //     }
        
    //     if(!empty($CurrDecode['data']['Installation_cost'])){
    //         if($CurrDecode['data']['Installation_cost'] == 'FREE'){
    //             $installation_cost = 0;
    //         }elseif($CurrDecode['data']['Installation_cost'] == 'Free'){
    //             $installation_cost = 0;
    //         }else{
    //             $installation_cost = $CurrDecode['data']['Installation_cost'];
    //         }
    //     }else{
    //         if($CurrDecode['data']['Installation_cost'] == '0'){
    //             $installation_cost = $CurrDecode['data']['Installation_cost'];
    //         }else{
    //             $installation_cost = 'N/A';
    //         }
    //     }
       
    //     return response()->json([
    //         'status'=>200,
    //         'message'=>$decode['message'],
    //         'expiry_date'=>$CurrDecode['data']['user_expire_time'], 
    //         'arrears_days' => $arrears_days,
    //         'user_available'=>$SelfProfile['user_available'],
    //         'data'=>$decode['data'], 
    //         'discount' => $this->discount, 
    //         'commercial_tax' => $this->commercial_tax,
    //         'installation_fee' => $installation_cost,
    //     ]);
    // }
    
    public function GetNewPackageInformation()
    {
        $access_token = $this->gettoken(); //get_token  
        $user_name    = request('user_name');
        
        $data         = "access_token={$access_token}&user_name={$user_name}";
        $url          = "api/v1/package/users-packages?".$data; 
        $output       = $this->GetFunction($url);
        $decode       = json_decode($output,true);

        $MbtUser      = MbtBindUser::where('user_name',$user_name)->first();
        $UserAvailable = array('Normal','Disabled','Stop Product','Paused','Non Activated');
     
        $Currurl             = "api/v1/user/view?".$data; 
        
        $Curroutput          = $this->GetFunction($Currurl);
        $CurrDecode          = json_decode($Curroutput,true);
     
        if(empty($CurrDecode['data']['Arrears_days'])){
            $arrears_days = 0;
        }else{
            $arrears_days = $CurrDecode['data']['Arrears_days'];
        }
      
        if($MbtUser)
        {
            $MbtBindUser = MbtBindUser::where('user_name',$user_name)->update([
             'er_id'                => $CurrDecode['data']['user_id'],
             'user_name'            => $CurrDecode['data']['user_name'],
             'user_real_name'       => $CurrDecode['data']['user_real_name'],
             'Monthly_Cost'         => $decode['data'][0]['billing_name'],
             'arrears_days'         => $arrears_days,
             'group_id'             => $CurrDecode['data']['group_id'],
             'region_id'            => $CurrDecode['data']['region_id'],
             'user_create_time'     => $CurrDecode['data']['user_create_time'],
             'user_update_time'     => $CurrDecode['data']['user_update_time'],
             'user_expire_time'     => $CurrDecode['data']['user_expire_time'],
             'user_status'          => $CurrDecode['data']['user_status'],
             'balance'              => $CurrDecode['data']['balance'],
             'mgr_name_create'      => $CurrDecode['data']['mgr_name_create'],
             'mgr_name_update'      => $CurrDecode['data']['mgr_name_update'],
             'user_start_time'      => $CurrDecode['data']['user_start_time'],
             'user_stop_time'       => $CurrDecode['data']['user_stop_time'],
             'phone'                => $CurrDecode['data']['phone'],
             'email'                => $CurrDecode['data']['email'],
             'create_visitor_num'   => $CurrDecode['data']['create_visitor_num'],
             'user_available'       => $CurrDecode['data']['user_available'],
             'question1'            => $CurrDecode['data']['question1'],
             'answer1'              => $CurrDecode['data']['answer1'],
             'question2'            => $CurrDecode['data']['question2'],
             'answer2'              => $CurrDecode['data']['answer2'],
             'question3'            => $CurrDecode['data']['question3'],
             'answer3'              => $CurrDecode['data']['answer3'],
             'school_type'          => $CurrDecode['data']['school_type'],
             'last_online'          => $CurrDecode['data']['last_online'],
             'last_offline'         => $CurrDecode['data']['last_offline'],
             'user_address'         => $CurrDecode['data']['user_address'],
             'salesman'             => $CurrDecode['data']['salesman'],
             'Area'                 => $CurrDecode['data']['Area'],
             'Router_type'          => $CurrDecode['data']['Router_type'],
             'other'                => $CurrDecode['data']['other'],
             'IPTV'                 => $CurrDecode['data']['IPTV'],
             'Installation_cost'    => $CurrDecode['data']['Installation_cost'],
             'Phone_number'         => $CurrDecode['data']['Phone_number'],
             //'city'                 => $CurrDecode['data']['city'],
             'GPS'                  => $CurrDecode['data']['GPS'],
             'Service_type'         => $CurrDecode['data']['Service_type'],
             'Bandwidth'            => $CurrDecode['data']['Bandwidth'],
             'Order_Received_Date'  => $CurrDecode['data']['Order_Received_Date'],
             'Promotion_type'       => $CurrDecode['data']['Promotion_type'],
             'Remark_Marketing'     => $CurrDecode['data']['Remark_Marketing'],
            //  'Monthly_Cost'         => $CurrDecode['data']['Monthly_Cost'],
            //  'Installation_person'  => $CurrDecode['data']['Installation_person'],
             'ODB_Box'              => $CurrDecode['data']['ODB_Box'],
             'Pon'                  => $CurrDecode['data']['Pon'],
             'LOID'                 => $CurrDecode['data']['LOID'],
             'Fiber_length'         => $CurrDecode['data']['Fiber_length'],
             'Initial_Contract_Validity'        => $CurrDecode['data']['Initial_Contract_Validity'],
             'Expected_installation_date'       => $CurrDecode['data']['Expected_installation_date'],
             'Installation_date'                => $CurrDecode['data']['Installation_date'],
             'Optical_power'            => $CurrDecode['data']['Optical_power'],
             'Remark'                   => $CurrDecode['data']['Remark'],
             'password'                 => $CurrDecode['data']['password'],
             'Abnormal_change'          => $CurrDecode['data']['Abnormal_change'],
             'User_maintenance_status'  => $CurrDecode['data']['User_maintenance_status'],
             'Fault_details'            => $CurrDecode['data']['Fault_details'],
             'Reporting_time'           => $CurrDecode['data']['Reporting_time'],
             'Supplementary_explanation' => $CurrDecode['data']['Supplementary_explanation'],
             'Marketing_maintenance'     => $CurrDecode['data']['Marketing_maintenance'],
             'Add_device_type'           => $CurrDecode['data']['Add_device_type'],
             'explanation'               => $CurrDecode['data']['explanation'],
             'DC_OCC'                    => $CurrDecode['data']['DC_OCC'],
             'LAN'                       => $CurrDecode['data']['LAN'],
             'delay_finance'             => $CurrDecode['data']['delay_finance'],
             'ODB_RX_Power'              => $CurrDecode['data']['ODB_RX_Power'],
             'Speed_Test'                => $CurrDecode['data']['Speed_Test'],
             'ODB_GPS'                   => $CurrDecode['data']['ODB_GPS'],
            //  'Reason_for_temporarily_unable_to_maintain' => $CurrDecode['data']['Reason_for_temporarily_unable_to_maintain'],
             'ALTERNATION_RECEIVE_DATE'                  => $CurrDecode['data']['ALTERNATION_RECEIVE_DATE'],
             'Replacement_days'                          => $CurrDecode['data']['Replacement_days'],
             'Nationality'                               => $CurrDecode['data']['Nationality'],
             'Sub_company'                               => $CurrDecode['data']['Sub_company'],
             'Now_package'                               => $decode['data'][0]['products_name'],
             'arrears_days'                              => $CurrDecode['data']['Arrears_days']
            ]);
        }
      
        if (array_key_exists($CurrDecode['data']['user_available'], $UserAvailable)) {
            $SelfProfile['user_available']      = $UserAvailable[$CurrDecode['data']['user_available']];
        }
        
        if(!empty($CurrDecode['data']['Installation_cost'])){
            if($CurrDecode['data']['Installation_cost'] == 'FREE'){
                $installation_cost = 0;
            }elseif($CurrDecode['data']['Installation_cost'] == 'Free'){
                $installation_cost = 0;
            }else{
                $installation_cost = $CurrDecode['data']['Installation_cost'];
            }
        }else{
            if($CurrDecode['data']['Installation_cost'] == '0'){
                $installation_cost = $CurrDecode['data']['Installation_cost'];
            }else{
                $installation_cost = 'N/A';
            }
        }
       
        return response()->json([
            'status'=>200,
            'message'=>$decode['message'],
            'expiry_date'=>$CurrDecode['data']['user_expire_time'], 
            'installation_date'=>$CurrDecode['data']['Installation_date'], 
            'arrears_days' => $arrears_days,
            'user_available'=>$SelfProfile['user_available'],
            'data'=>$decode['data'], 
            'discount' => $this->discount, 
            'commercial_tax' => $this->commercial_tax,
            'installation_fee' => $installation_cost,
            'new_user_days' => $this->new_user_days,
            'new_user_days1' => $this->new_user_days1,
        ]);
    }
    
    public function StoreNewPayment(Request $request)
    {
        $validator = \Validator::make($request->all(),
        [
           'user_name'   => 'required',
           'payment_id'  => 'required',
           'amount'      => 'required',
           'begin_date'  => 'required',
           'expire_time' => 'required',
           'phone'       => 'required',
        ]);
       
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
        $discount = $this->discount;
        $commercial_tax = $this->commercial_tax;
        $amount = $request->amount; // 113700
        $installation_cost = $request->installation_cost;
        
        $amount1 = number_format($amount/(1 + $discount/100), 0, '.', ''); // 103364
        $amount2 = number_format($amount1/(1 + $commercial_tax/100), 0, '.', ''); // 98442
        $amount3 = $amount2 + $installation_cost; // 148442
        
        $total_amount = $amount3;
       
        $amount4 = $amount3 - $installation_cost; // 98442
        $amount5 = number_format($amount4 * (1 + $commercial_tax/100), 0, '.', ''); // 103364
        $amount6 = number_format($amount5 * (1 + $discount/100), 0, '.', ''); // 113700
        
        $phone = $request->phone;
        $data = [];
        
        $data['user_name']          = $request->user_name;
        $data['Expire_time']        = $request->expire_time;
        $data['user_available']     = 0;
        $data['Arrears_days']       = 0;
        
        $payment_new                = new PendingPayment();
        $payment_new->user_name     = $request->user_name;
        $payment_new->order_no      = uniqid();
        $payment_new->payment_id    = $request->payment_id; 
        $payment_new->amount        = $amount; 
        $payment_new->number        = uniqid(); 
        $payment_new->begin_date    = $request->begin_date;
        $payment_new->expire_time   = $request->expire_time;
        $payment_new->phone         = $request->phone;
        $payment_new->discount      = $discount;
        $payment_new->commercial_tax= $commercial_tax;
        $payment_new->installation_cost= $installation_cost;
        $payment_new->save();
        
        if($payment_new->id){
            $check_payment = PendingPayment::where('id', $payment_new->id)->first();
            if($check_payment->expire_time == 'NaN-NaN-NaN 23:00:00'){
                $remove_payment = PendingPayment::where('id', $payment_new->id)->delete();
                return response()->json([
                    'status'=>405,
                    'message'=>'Something went wrong!.'
                ]);
            }
        }
          
        if($request->payment_id == '12'){
            date_default_timezone_set('Asia/Yangon');
        
            $api_url          = $this->direct_apiurl;
            $fldMerchCode     = $this->direct_mcode;
            $key              = $this->direct_key;
            
            $fldTxnCurr       = 'MMK';
            $fldTxnAmt        = $amount;
            $fldTxnScAmt      = '0';
            $fldSucStatFlg    = 'N';
            $fldFailStatFlg   = 'N';
            $fldClientCode    = '101';
            $fldMerchRefNbr   = $payment_new->order_no;
            $fldDatTimeTxn    = date("d/m/YH:i:s", strtotime('+0 hours'));
            
            $string = 'fldClientCode='.$fldClientCode.'|fldMerchCode='.$fldMerchCode.'|fldTxnCurr='.$fldTxnCurr.'|fldTxnAmt='.$fldTxnAmt.'|fldTxnScAmt='.$fldTxnScAmt.'|fldMerchRefNbr='.$fldMerchRefNbr.'|fldSucStatFlg='.$fldSucStatFlg.'|fldFailStatFlg='.$fldFailStatFlg.'|fldDatTimeTxn='.$fldDatTimeTxn;
            $md5_enc = md5($string);
            $new_string = $string.'|checkSum='.$md5_enc;
            //dd($new_string);
               
        }else{
              $key = 'N/A';
              $new_string = 'N/A';
        }
          
        return response()->json([
            'status'=>200,
            'order_no'=>$payment_new->order_no,
            'amount'=>$payment_new->amount,
            'key' => $key,
            'testdata' => $new_string,
            'message'=>'Payment request successfull.',
            'username'=>$request->user_name
        ]);
    }
    
    public function printinvoice(Request $request) 
    {
        $trans_id = $request->trans_id;
        
        $payment = PaymentNew::where('id', $trans_id)->first();
        
        if(!empty($payment)){
            $pdf_url = url('invoice/'.$trans_id);
            return response()->json([
                'status'=>200,
                'message'=>'Invoice Available',
                'pdf_url'=>$pdf_url
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'Invoice Not Available',
                'pdf_url'=>null
            ]);
        }
        
    }
    
    public function check_maintenance() 
    {
        $maintenance = Setting::where('id', '1')->first();
        if(!empty($maintenance->maintenance_mode) && $maintenance->maintenance_mode == 'on'){
            return response()->json([
                'status'=>200,
                'message'=>'Maintenance Available',
                'data'=>null
            ]);
        }else{
            return response()->json([
                'status'=>400,
                'message'=>'No Maintenance Available',
                'data'=>null
            ]);
        }
    }
    
}









