<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\User;
use App\Http\Controllers\Controller;
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
use App\Models\Daynamicpage;
use App\Models\MbtBindUser;
use App\Models\PaymentQuery;
use App\Models\Testimonial;
use App\Models\PaymentGatewey;
use App\Models\FaultReportQuery;
use App\Models\UserQuery;
use App\Models\PermissionModel;
use App\Models\SubCompany;
use Illuminate\Support\Facades\File;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class LoginController extends Controller
{
    public function register(Request $req)
    {
        // echo("hello");
        $validator = \Validator::make($req->all(),
           [
                'phone'    => 'required|unique:users|min:7|max:15',
                'name'     => 'required',
                'password' => 'required|min:6',
                'confirm_password' => 'required_with:users|same:password|min:6',
                // 'device_type'      => 'required',
                // 'device_id'        => 'required'
           ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
        // NOTE: new_pass stores plaintext for legacy display (masked in admin views)
        $data = array(
                'phone'     => $req->input('phone'),
                'uniq_id'   => rand(000000,999999),
                'name'      => $req->input('name'),
                'password'  => Hash::make($req->password),
                'new_pass'  => $req->password, // Legacy field - masked in UI
                // 'device_type'  => $req->device_type,
                // 'device_id'    => $req->device_id,
        );
        $article= User::create($data);
        $userdata['NormalData'] = User::where('id',$article->id)->first();
        //dd($article->id);
        return response()->json(['status'=>200,'message'=>'User Registered Successfully!','user_data'=>$userdata]);
    
    }
    public function CheckMobileNumber(Request $req)
    {
        $validator = \Validator::make($req->all(),
           [
                'phone' => 'required|unique:users|min:7|max:15',
                'name' => 'required',
                'password' => 'required|min:6',
                'confirm_password' => 'required_with:users|same:password|min:6|max:15'
           ]);
        if($validator->fails())
        {
            $response['status'] = 400;
            $response['response'] = $validator->messages();
            return $response;
        }else
        {
            $response['status'] = 200;
            $response['response'] = 'all variables are fine';
            return $response;
        }
       
    }
    public function login(Request $req)
    {   
        $validator = \Validator::make($req->all(),
         [
            'phone' => 'required|min:7|max:15',
            'password' => 'required|min:6',
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
         $check=User::where('phone',$req->phone)->where('user_status',1)->first();
        if(!empty($check)){
            
                      return response()->json(['status'=>401,'message'=>'Account has been disabled please contact to support team']);
        }
        
        if(Auth::attempt(['phone'=>$req->phone,'password'=>$req->password,'user_status'=>'0']))
        {
            $update = User::where('id',Auth::user()->id)->update(['device_type' => $req->device_type,'device_id'=> $req->device_id,]);
            $data['NormalData'] = Auth::user();
            if(Auth::user()->bind_id == 1)
            {
                $data['MbtData'] = MbtBindUser::where('er_id',Auth::user()->bind_user_id)->first();
            }
            return response()->json(['status'=>200,'message'=>'User Login  Successfully!','user_data'=>$data]);
        }
        else
        {
          return response()->json(['status'=>400,'message'=>'Cant find any data in our record!']);
        }
    }
   
   public function GetPaymentRecord(Request $request)
   {
       $validator = \Validator::make($request->all(),
         [
            'username' => 'required',
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
       $data = PaymentQuery::join('payment_gateweys','payment_gateweys.id','=','payment_query.payment_method')
                            ->where('payment_query.payment_user_name',$request->username)
                            ->select('payment_query.*','payment_gateweys.title as payment_gateway')
                            ->get();
       if($data)
        {
           return response()->json(['status'=>200,'message'=>'Query Get Successfully!','query'=>$data]);
        }
        else
        {
          return response()->json(['status'=>400,'message'=>'Cant find any data in our record!']);
        }
   }
   public function forgot_password(Request $request)
     { 
          $email =$request->input('email');
           if($user = DB::table('users')->where('email',$email)->first())
           {
           $data=[
                 'name'=>"manish",
                 'data'=>"hello manish"
                 ];
                 
             $userto='rajhimanshu111222@gmail.com';
             
             Mail::send('email',$data, function($messages) use ($userto)
             {
             $messages->to($userto);
             $messages->subject('Virat Gandhi');
             });
          echo "Basic Email Sent. Check your inbox.";
        
           }
          else
          {
          return response()->json(['status'=>400,'message'=>'We can not find a user with that e-mail address!']);
          }
     }
     
     public function ApplyInstallBroadband(Request $request)
     {
         $validator = \Validator::make($request->all(),
         [
            'contact_name' => 'required',
            'user_number'  => 'required|unique:user_query',
            'address'      => 'required',
            'user_id'      => 'required',
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        $userQuery = new UserQuery();
        $userQuery->contact_name = $request->contact_name;
        $userQuery->user_number  = $request->user_number;
        $userQuery->address      = $request->address;
        $userQuery->user_id      = $request->user_id;
        $userQuery->reporting_time      = $request->reporting_time;
        $userQuery->apply_date_start = date('Y-m-d');
        $userQuery->save();
        return response()->json(['status'=>200,'message'=>'Apply for broadband is Successfully done!']);
         
     }
     
     public function GetApplyQuery(Request $request)
     {
         $validator = \Validator::make($request->all(),
         [
           'user_id'      => 'required',
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        $status = [1=>'Accept',3=>'Processing',4=>'Complete',2=>'Not Accept'];
        $query = UserQuery::where('user_id',$request->user_id)->get();
        if(count($query)>0)
        {
            foreach($query as $value)
             {
                if (array_key_exists($value->query_status, $status)){
                   $value->query_status = $status[$value->query_status]; 
                }
             }
           return response()->json(['status'=>200,'message'=>'Apply query get successfully','query'=>$query]);
        }
        else
        {
          return response()->json(['status'=>400,'message'=>'Sorry cant find any apply query!']);
        }
       
     }
     public function UpdateApplyQuery(Request $request)
     {
         $validator = \Validator::make($request->all(),
         [
           'user_id'      => 'required',
           'apply_id'     => 'required',
           'query_status' => 'required'
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        $status = [1=>'Accept',3=>'Processing',4=>'Complete',2=>'Not Accept'];
        $query = UserQuery::where(['user_id'=>$request->user_id,'apply_id'=>$request->apply_id])->update(['query_status'=>$request->query_status]);
        if($query)
        {
           return response()->json(['status'=>200,'message'=>'Apply query updtae successfully']);
        }
        else
        {
          return response()->json(['status'=>400,'message'=>'Sorry cant update this apply query!']);
        }
       
     }
   public function change_number(Request $request)
     {
         $validator = \Validator::make($request->all(),
         [
            'new_phone' => 'required',
            'phone'     => 'required|exists:users',
           
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
          $phonenumber=$request->phone;
          $newnumber=$request->new_phone;
        if(User::where('phone',$phonenumber)->first())
        {
            if(User::where('phone',$newnumber)->first())
            {
                return response()->json(['status'=>400,'message'=>'Already exist']);

            }
            else {
                 User::where('phone',$phonenumber)->update(['phone'=>$newnumber]);
                 return response()->json(['status'=>200,'message'=>'Number update!']);
            }
            
        }
        else
        {
          return response()->json(['status'=>400,'message'=>'We can not find a user with that Phone number!']);
        }
     }
     
     public function change_Password (Request $request)
     {
          $validator = \Validator::make($request->all(),
         [
             'current_password' => 'required',
             'new_password' => 'required',    
             'user_id'   => 'required',
             'confirm_password' => 'required_with:users|same:new_password'
           
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
        $current_password=$request->current_password;
        $new_password=$request->new_password;
        
        
        if($user = User::where('id',$request->user_id)->first())
        {    
            if(Auth::attempt(['phone'=>$user->phone,'password'=>$current_password]))
            {
                User::where('id',$request->user_id)->update(['password'=>Hash::make($new_password)]);
                return response()->json(['status'=>200,'message'=>'User password update Successfully!']);
            }else
            {
                return response()->json(['status'=>400,'message'=>'current password does not match!']);
            }
        }
        else
        {
          return response()->json(['status'=>400,'message'=>'We can not find a user with that user id!']);
        }
         
     }
     public function forgotPassword(Request $request)
     {
            $validator = \Validator::make($request->all(),
         [
             'new_password' => 'required', 
             'phone' =>'required',
             'confirm_password' => 'required_with:users|same:new_password'
           
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        $new_password=$request->new_password;
        
        if($user = User::where('phone',$request->phone)->first())
        { 
            User::where('phone',$request->phone)->update(['password'=>Hash::make($new_password)]);
            return response()->json(['status'=>200,'message'=>'User password update Successfully!']);
        }
        else
        {
          return response()->json(['status'=>400,'message'=>'We can not find a user with that user id!']);
        }
         
     }
     
     public function Preferential_activities(Request $request)
     {
       $banner = DB::table('Preferentialactivities')->select('name','image')->where('rating',1)->get();
	     if(count($banner) > 0)
         {
             $url = asset("assets/front/banner/");
             return response()->json(['status'=>200,'message'=>'Get Preferential activities  successfully!','base_url'=>$url,'banner'=>$banner]);
         }else
         {
             return response()->json(['status'=>400,'message'=>'cant find any Preferential activities !']);
         }
     }
     
     public function profileimage(Request $request)
     {
        $validator = \Validator::make($request->all(),
         [
             'profile_image' => 'required',
             'user_id'  => 'required'
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        
       $imagedir       = "/home/mbtcom/public_html/telco/assets/user";
     //dd(public_path());
        $image = $request->profile_image;
        if(isset($image)) {
            $upload_dir = $imagedir;
            $img =$image;
            $type= ".jpg";
            $img = str_replace('data:image/jpeg;base64,', '', $img);
            $img = str_replace(' ', '+', $img);
            $datas = base64_decode($img);
            $fileName = strtolower(time() . $type);
            $file = $upload_dir .'/'. $fileName;
            File::put($file, $datas);
            User::where('id',$request->user_id)->update(['photo'=>$fileName]);
            //$img = "https://telco.mbt.com.mm/assets/user/".$fileName;
            $url = "https://telco.mbt.com.mm/assets/user/";
            
            $data['NormalData'] = User::where('id',$request->user_id)->first();
            if($data['NormalData']->bind_id == 1)
            {
                $data['MbtData'] = MbtBindUser::where('er_id',$data['NormalData']->bind_user_id)->first();
            }
            return response()->json(['status'=>200,'message'=>'Profile update successfully!','base_url'=>$url,'user_data'=>$data]);
        } else {
            return "";
        }
     }
     
     public function error_code(Request $request)
     {
        $error = DB::table('error_code')->get();
        
        if($request->input('lang_id')==0)
        {
        if(count($error) > 0)
         {
            foreach($error as $errormessage)
            {
              $arr1[] = $errormessage->key;
              $arr2[] = $errormessage->value;
            //   $arr3[] = $errormessage->burmese_language;
            //   $arr4[] = $errormessage->chinese_language;
            }
            $message= array_combine($arr1,$arr2);
          return response()->json(['status'=>200,'message'=>'Get Error code successfully!','errormessage'=>$message]);
         }
        }
        
        
        
        if($request->input('lang_id')==2)
        {
        if(count($error) > 0)
         {
            foreach($error as $errormessage)
            {
              $arr1[] = $errormessage->key;
              $arr2[] = $errormessage->burmese_language;
            //   $arr3[] = $errormessage->burmese_language;
            //   $arr4[] = $errormessage->chinese_language;
            }
            $message= array_combine($arr1,$arr2);
          return response()->json(['status'=>200,'message'=>'Get Error code successfully!','errormessage'=>$message]);
         }
        }
        
        
        
        
        
        if($request->input('lang_id')==1)
        {
        if(count($error) > 0)
         {
            foreach($error as $errormessage)
            {
              $arr1[] = $errormessage->key;
              $arr2[] = $errormessage->chinese_language;
            //   $arr3[] = $errormessage->burmese_language;
            //   $arr4[] = $errormessage->chinese_language;
            }
            $message= array_combine($arr1,$arr2);
          return response()->json(['status'=>200,'message'=>'Get Error code successfully!','errormessage'=>$message]);
         }
        }
        
        
        
         
        else
        {
           return response()->json(['status'=>400,'message'=>'cant find any Error Code !']);
        }
      }
      
      public function insertmessage(Request $request)
      {
           $validator = \Validator::make($request->all(),
         [
             'userid' => 'required',
             'message'  => 'required'
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
       $userid =$request->input('userid');
       $message =$request->input('message');
       $reciever_userid  =1;
       
         date_default_timezone_set('Asia/yangon');
        $ldate=date('Y-m-d h:i:s');
        
       
        DB::table('users') ->where('id', $request->userid)->update(['last_msg_status' => 0, 'last_msg_date' => $ldate]); 

       $result= DB::table('chat')->insert(['sender_userid' => $userid, 'message'=>$message, 'reciever_userid'=>$reciever_userid]);
       
       //$result= DB::table('admin_message_noti')->insert(['sender_userid' => $userid, 'message'=>$message, 'reciever_userid'=>$reciever_userid,'read_status'=>1]);
       
       // Update read status
       //$result=  DB::table('pro_orders_has_passengers')->where('title_name','$userid')->update(['title_name' => 'mr',]);
       
        if($result==1)
        {
            return response()->json(['status'=>200,'message'=>'Message send successfully!']);
       }
        else
        {
        return response()->json(['status'=>400,'message'=>'message not send!']);
        }

      }
      
      
       public function get_message(Request $request)
      {
           $validator = \Validator::make($request->all(),
         [
             'userid' => 'required',
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        $userid=$request->input('userid');
        $result= DB::table('chat')->where(['sender_userid' => $userid])->orWhere(['reciever_userid' => $userid])->orderBy('timestamp', 'DESC')->get();        
          if(count($result) >0)
          {
            return response()->json([
                 'message'=>'All Chat',
                 'status'=>200,
                 'data'=>$result,
          ]);
        }
     else
        {
        return response()->json(['status'=>400,'message'=>'Message  not get!']);
        }
      }
      
     public function aboutmessage(Request $request)
     { 
         $validator = \Validator::make($request->all(),
         [
             'language_id' => 'required',
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        if($request->language_id == 0)
        {
            $data = Daynamicpage::select('id','title','content','created_at','updated_at')->first();
            
        }elseif($request->language_id == 1)
        {
            $data = Daynamicpage::select('id','title','chinese  as content','created_at','updated_at')->first();
        }elseif($request->language_id == 2)
        {
            $data = Daynamicpage::select('id','title','burmish as content','created_at','updated_at')->first();
        }
        else
        {
            return response()->json(['status'=>200,'message'=>'Data not found']);

        }
        return response()->json(['status'=>200,'message'=>'About us page data get successfully','data'=>$data]);
     }
     
     public function paymentmethod()
     {
        $user = DB::table('payment_gateweys')
               ->whereIn('id', [12,8,9,11,10])
               ->where('status', '1')
               ->orderBy('sorting','ASC')->get();
        $days = Setting::where('id', '1')->first();
        return response()->json(['status'=>200,'message'=>'Payment Method','data'=>$user,'check_days'=>$days->package_expiry_days]);
     }
     
      public function second_login(Request $req)
    {   
        $validator = \Validator::make($req->all(),
         [
            'phone' => 'required|min:7|max:15',
            'password' => 'required|min:6',
            'id' => 'required',
         ]);
        if($validator->fails())
        {
            $response['response'] = $validator->messages();
            return $response;
        } 
        if(Auth::attempt(['phone'=>$req->phone,'password'=>$req->password,'id'=>$req->id]))
        {
            return response()->json(['status'=>200,'message'=>'User Login  Successfully!']);
        }
        else
        {
          return response()->json(['status'=>400,'message'=>'Cant find any data in our record!']);
        }
    }
    
    /* Login with */
    
     public function loginotp(Request $request)
    {
       $mobilenum  = $request->input('phone');
        $validator = \Validator::make($request->all(),
         [
            'phone' => 'required|min:7|max:15',
         ]);
            if($validator->fails())
            {
                $response['response'] = $validator->messages();
                return $response;
            } 
            else 
            {
                $user  = User::where(['phone'=>$mobilenum])->get();
                if(count($user)>0)
                 {
                    $phone  = $request->input('phone');
                    $otp = rand(111111,999999);
                    $url ='https://smspoh.com/api/v2/send/';
                    $field = array('to'=>$mobilenum,'message'=>'Dear customer, ' .$otp.' is your MBT OTP code','sender'=>'Info');
                    $fields =json_encode($field);
                    $ch = curl_init();
                    //set options 
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json","Authorization: Bearer Ro3zZa98cPCSGkAxp8PdInXHl94riPSQA3wmMDClavmnzJ7uQx3hyu7XINtXOCsY")); // Live Token
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); 
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //needed so that the $result=curl_exec() output is the file and isn't just true/false
                    //execute post
                    $result = curl_exec($ch);
                    //close connection
                    curl_close($ch);
                    $response=json_decode($result, true);
                    // print_r($response);
                    // die();
                  if($response['data']['messages'][0]['operator'] == 'Unknown')
                    {
                        
                         return response()->json(['status'=>400,'message'=>'Phone number is not correct']);
                
                    }else
                    {
                        
                        return response()->json(['status'=>200,'message'=>'Otp sent successfully','Otp' =>$otp]);
                    }
                  }
                 else
                 {
                   return response()->json(['status'=>400,'message'=>'Cant find any data in our record!']);
                 }
            }
      }
      
          
    /* Login with */
    
     public function signupot(Request $request)
    {
       $mobilenum  = $request->input('phone');
        $validator = \Validator::make($request->all(),
         [
            'phone' => 'required|min:7|max:15',
         ]);
                if($validator->fails())
                {
                    $response['response'] = $validator->messages();
                    return $response;
                } 
                else 
                {      
                    $phone  = $request->input('phone');
                    $otp = rand(111111,999999);
                    $url ='https://smspoh.com/api/v2/send/';
                    $field = array('to'=>$mobilenum,'message'=>'Dear customer, ' .$otp.' is your MBT OTP code','sender'=>'Info');
                    $fields =json_encode($field);
                    $ch = curl_init();
                    //set options 
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json","Authorization: Bearer Ro3zZa98cPCSGkAxp8PdInXHl94riPSQA3wmMDClavmnzJ7uQx3hyu7XINtXOCsY")); // Live Token
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); 
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //needed so that the $result=curl_exec() output is the file and isn't just true/false
                    //execute post
                    $result = curl_exec($ch);
                    //close connection
                    curl_close($ch);
                    $response=json_decode($result, true);
                    // print_r($response);
                    // die();
                  if($response['data']['messages'][0]['operator'] == 'Unknown')
                    {
                        return response()->json(['status'=>400,'message'=>'Phone number is not correct']);
                
                    }else
                    {
                         //$result= DB::table('users')->insert(['phone' => $mobilenum]);
                        
                        return response()->json(['status'=>200,'message'=>'otp sent successfully','Otp' =>$otp]);
                    }
                 
            }
      }
}

