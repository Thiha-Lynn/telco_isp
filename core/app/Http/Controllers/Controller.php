<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\BankSetting;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
       
        public function directpay()
        {  
            $data['details'] = BankSetting::where('id', '1')->first();
            return view('directpay', $data);
        }
       
        public function submitdirectpay()
       {  
            //   $curl = curl_init();
            //   echo $curl;
             
            //   curl_setopt_array($curl, array(
            //   CURLOPT_URL => 'https://mirror.kbzbank.com/B001/directpay',
            //   CURLOPT_RETURNTRANSFER => true,
            //   CURLOPT_ENCODING => '',
            //   CURLOPT_MAXREDIRS => 10,
            //   CURLOPT_TIMEOUT => 0,
            //   CURLOPT_FOLLOWLOCATION => true,
            //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //   CURLOPT_CUSTOMREQUEST => 'POST',
            //   CURLOPT_POSTFIELDS => 'encdata=sdaDzJZANsZWIHPDSX6%2BKOft%2BjSFvLgAUY6mzMiaadIIS6xCImlm0kSWHBsXNX0v2UOhN5GAxbpwE2k%2FGX3PBwEPhVOINg45o591%2BOs8pTD3AL7HIHUA5ydGuca1GC8V%2BJQsBOP1NrdSjJ92EFmk97w0E2BwJP7K7syqpfZ3R9dmUCsKCW9%2FaNBGxArqoS2jkr4vDn8c5FqlZJEo6ncSDiBfCY56wMQ%2Fx1ZcfiZcUIgOwPKSmGq%2BNVh6Vsxdts%2FsCcfP8yIpmsclAroVlY%2FbuLe7N8BSBNdNiezvA8vVDvo%3D',
            //   CURLOPT_HTTPHEADER => array(
            //     'Content-Type: application/x-www-form-urlencoded',
            //     'Cookie: JSESSIONID=E4OIKU_M_buHWzC8URLA8FnmqAUhT0UEMRvJZdtIZ0tPU_iW79xf!2067723662; TS01c3bd7a=0194edf17fb5c59dbc418741feddbc000c9936a6118f76a9804955b4ee0ae2b7d73cc66e0fcc0f03b6b9600f212c9be00731876443839db43094c46fc898c7e7097a8b8440; TS01455be8=0194edf17f46374861f9196205f3d3808c4807a93c3f9d4ee96caf6a369217a5eb3b6c361b6241f87fff0bcdd56dcc403fd2a30130; __cfruid=5b668741a9c5bb0286c4f77cc81c72968fc519b9-1647235420'
            //   ),
            // ));
            
            // $response = curl_exec($curl);
            
            // curl_close($curl);
            // echo $response;
            
           return view('directpaysubmit');
       }
   
   
}


