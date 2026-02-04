<?php

namespace App\Http\Controllers\Admin;

use Session;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\PendingPayment;
use App\Models\Setting;
use App\Models\BankSetting;
use App\Models\PaymentNew;
use App\Models\PaymentQuery;
use App\Models\MbtBindUser;
use App\Models\WaveCallback;

class PendingController extends Controller
{
    public $lang;
    public function __construct()
    {
        $this->lang = Language::where('is_default',1)->first();
        
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
    
    public function GetFunction($url)
    {
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
    
    public function paymentRecordsInsertWithDate($user_name,$begin,$phone,$expire,$invoice_no,$transaction_id)
    {
        $access_token = $this->gettoken(); //get_token
        $data         = "access_token={$access_token}&user_name={$user_name}";
        $url          = "api/v1/financial/payment-records?".$data; 
        $output       = $this->GetFunction($url);
        $decode       = json_decode($output,true);
        $value        = $decode['data']['0'];
 
        $SubComId = MbtBindUser::where('user_name',$user_name)->first(['sub_company','er_id']);
        
        $check_payment = PaymentNew::where('order_id', $value['order_no'])->first();
        if(empty($check_payment) && !empty($SubComId)){

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
            $payment->transaction_id    = $transaction_id;
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
            $payment_new->transaction_id    = $transaction_id;
            $payment_new->save();
        }
    }
    
    public function failedpaymentRecordsInsertWithDate($user_name,$begin,$phone,$expire,$invoice_no,$order_id,$total_amt,$payment_method,$created_at)
    {
        $SubComId = MbtBindUser::where('user_name',$user_name)->first(['sub_company','er_id']);
        if(!empty($SubComId)){
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
    }
    
    public function getpaymentrecords($user_name,$begin,$phone,$expire,$invoice_no)
    {
        $access_token = $this->gettoken(); //get_token
        $data         = "access_token={$access_token}&user_name={$user_name}";
        $url          = "api/v1/financial/payment-records?".$data; 
        $output       = $this->GetFunction($url);
        $decode       = json_decode($output,true);
        $value        = $decode['data']['0'];
      
        $order_no = $value;
      
        return $order_no;
    }

    public function cbpay()
    {
        $payments = PendingPayment::where('payment_id', '11')->where('status', '0')->where('created_at', '>', '2024-03-11 12:20:05')->orderBy('id', 'DESC')->get();
        
        return view('admin.pending.cbpay', compact('payments'));
    }
    
    public function update_cbpay()
    {
        $orders = PendingPayment::where('payment_id', '11')->where('status', '0')->where('created_at', '>', '2024-03-11 12:20:05')->orderBy('id', 'DESC')->get();
        foreach($orders as $order){
            $user_name   = $order->user_name;
            $oreder_id   = $order->order_no;
            $payment_id  = $order->payment_id;
            $amount      = $order->amount;
            $number      = $order->number;
            $begin_date  = $order->begin_date;
            $expire_time = $order->expire_time;
            $phone       = $order->phone;
            $oreder_ref  = $order->generateRefOrder;
            
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
                if($decode['code'] == 0 || $decode['code'] == 10702)
                {
                    $data['access_token']       = $access_token;
                    $data['user_name']          = $order->user_name;
                    $data['Expire_time']        = $order->expire_time;
                    $data['user_available']     = 0;
                    $data['Arrears_days']       = 0;
                  
                    $UpadteUser = $this->UpdateUser($data);
                  
                    $this->paymentRecordsInsertWithDate($order->user_name,$order->begin_date,$order->phone,$order->expire_time,$invoice_no,$response['transactionId']);
                      
                    $rs_mbt = $this->getpaymentrecords($order->user_name,$order->begin_date,$order->phone,$order->expire_time,$invoice_no);
                    
                    PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                }
            }else{
                $this->failedpaymentRecordsInsertWithDate($order->user_name,$order->begin_date,$order->phone,$order->expire_time,$number,$oreder_id,$order->amount,$order->payment_id,$order->created_at);
                PendingPayment::where('order_no', $oreder_id)->update(['status' => '2']);
            }
        }
        
        return redirect()->back()->with('message', 'Process is finished!');
    }
    
    public function kbzpay()
    {
        $payments = PendingPayment::where('payment_id', '8')->where('status', '0')->where('created_at', '>', '2024-03-11 12:20:05')->orderBy('id', 'DESC')->get();
        
        return view('admin.pending.kbzpay', compact('payments'));
    }
    
    function generateRandomString($length = 32) {   
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    public function update_kbzpay()
    {
        $orders = PendingPayment::where('payment_id', '8')->where('status', '0')->where('created_at', '>', '2024-03-11 12:20:05')->orderBy('id', 'DESC')->get();
        foreach($orders as $order){
            
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
            
            $user_name   = $order->user_name;
            $oreder_id   = $order->order_no;
            $payment_id  = $order->payment_id;
            $amount      = $order->amount;
            $number      = $order->number;
            $begin_date  = $order->begin_date;
            $expire_time = $order->expire_time;
            $phone       = $order->phone;
            $oreder_ref  = $order->generateRefOrder;
            
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
                if($decode['code'] == 0 || $decode['code'] == 10702)
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
                }
            }else{
                $this->failedpaymentRecordsInsertWithDate($order->user_name,$order->begin_date,$order->phone,$order->expire_time,$number,$oreder_id,$order->amount,$order->payment_id,$order->created_at);
                PendingPayment::where('order_no', $oreder_id)->update(['status' => '2']);
            }
        }
        
        return redirect()->back()->with('message', 'Process is finished!');
    }
    
    public function wavepay()
    {
        $payments = PendingPayment::where('payment_id', '10')->where('status', '0')->where('created_at', '>', '2024-03-11 12:20:05')->orderBy('id', 'DESC')->get();
        
        return view('admin.pending.wavepay', compact('payments'));
    }
    
    public function update_wavepay()
    {
        $orders = PendingPayment::where('payment_id', '10')->where('status', '0')->where('created_at', '>', '2024-03-11 12:20:05')->orderBy('id', 'DESC')->get();
        foreach($orders as $order){
            
            $user_name   = $order->user_name;
            $oreder_id   = $order->order_no;
            $payment_id  = $order->payment_id;
            $amount      = $order->amount;
            $number      = $order->number;
            $begin_date  = $order->begin_date;
            $expire_time = $order->expire_time;
            $phone       = $order->phone;
            $oreder_ref  = $order->generateRefOrder;
            
            $payment = WaveCallback::where('orderId', $oreder_id)->first();
                
            if(!empty($payment->status) && $payment->status == 'PAYMENT_CONFIRMED'){
            
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
                if($decode['code'] == 0 || $decode['code'] == 10702)
                {
                    $data['access_token']       = $access_token;
                    $data['user_name']          = $order->user_name;
                    $data['Expire_time']        = $order->expire_time;
                    $data['user_available']     = 0;
                    $data['Arrears_days']       = 0;
                  
                    $UpadteUser = $this->UpdateUser($data);
                  
                    $this->paymentRecordsInsertWithDate($order->user_name,$order->begin_date,$order->phone,$order->expire_time,$invoice_no,$payment->transactionId);
                      
                    $rs_mbt = $this->getpaymentrecords($order->user_name,$order->begin_date,$order->phone,$order->expire_time,$invoice_no);
                    
                    PendingPayment::where('order_no', $oreder_id)->update(['status' => '1']);
                }
            }else{
                $this->failedpaymentRecordsInsertWithDate($order->user_name,$order->begin_date,$order->phone,$order->expire_time,$number,$oreder_id,$order->amount,$order->payment_id,$order->created_at);
                PendingPayment::where('order_no', $oreder_id)->update(['status' => '2']);
            }
        }
        
        return redirect()->back()->with('message', 'Process is finished!');
    }
}