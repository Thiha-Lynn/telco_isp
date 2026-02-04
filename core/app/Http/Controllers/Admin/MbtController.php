<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use App\Models\PaymentQuery;
use App\Models\MbtBindUser;
use App\Models\PaymentGatewey;
use App\Models\SubCompany;
use App\Models\User;
use App\Models\Setting;

class MbtController extends Controller
{
    public $successStatus      = 200;
    public $unauthorisedStatus = 400;
    
    public function __construct()
    {
        $settings = Setting::where('id', '1')->first();
        $this->base_url = $settings->ip_address;
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
		//$outobj = json_decode($output,true);
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
	    //dd($access_token);
	    $dataobj["access_token"]    = $access_token;
    	$dataobj["username"]        = request('user_name');
    	$dataobj["user_real_name"]  = request('user_real_name');	
    	$dataobj["user_password"]   = request('user_password');
    	$dataobj["group_id"]        = request('group_id');
    	$dataobj["products_id"]     = request('products_id');
   // dd($dataobj);
	    $url                        = "api/v1/users"; 
	    $output                     = $this->PostFunction($url,$dataobj);
	    print_r($output);
	}
	//View User from MBT server
	public function ViewUser()
	{
	    $access_token = $this->gettoken(); //get_token
	    $access_token    = $access_token;
    	$user_name       = request('user_name');
    	$data            = "access_token={$access_token}&user_name={$user_name}";
        $url             = "api/v1/user/view?".$data; 
	    $output          = $this->GetFunction($url);
	    print_r($output);
	}
	
	//Get Paymemt Records
	public function paymentRecords($lang,$username)
	{
	    $access_token = $this->gettoken(); //get_token
	    $user_name    = decrypt($username); 
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
	                $payment->trans_date        = date('d M Y',$value['create_at']);
	                $payment->total_amt         = $value['pay_num'];
	                $payment->invoice_no        = $value['bill_number'];
	                $payment->payment_method    = $value['pay_type_id'];
	                $payment->package_id        = $value['package_id']; 
	                $payment->product_id        = $value['product_id'];
	                $payment->save();
	            } 
	        }
	    $outdata['payment_gateway']   = PaymentGatewey::where('status',1)->get();
	    $outdata['user']      = User::all();
        $outdata['sub_com']   = SubCompany::all();
	    $outdata['payment']   = PaymentQuery::where('payment_user_name',$user_name)->get();
	    }else
	    {
	        $outdata['payment_gateway']   = PaymentGatewey::where('status',1)->get();
    	    $outdata['user']      = User::all();
            $outdata['sub_com']   = SubCompany::all();
    	    $outdata['payment']   = PaymentQuery::where('payment_user_name',$user_name)->get();
	    }
	    return view('admin.payment.index',$outdata);
	}

	
}










